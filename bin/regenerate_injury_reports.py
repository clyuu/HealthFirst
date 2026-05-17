from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path

import mysql.connector


BASE_DIR = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(BASE_DIR))

from ai_service.utils.reporting import build_injury_report

LABELS = ["Burns", "Cuts & Bleeding", "Normal (No Visible Injury)"]


def load_env() -> dict[str, str]:
    values: dict[str, str] = {}
    env_path = BASE_DIR / ".env"
    if not env_path.is_file():
        return values

    for line in env_path.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        values[key.strip()] = value.strip().strip('"').strip("'")
    return values


def db_config() -> dict:
    env = load_env()
    return {
        "host": env.get("DB_HOST", "127.0.0.1"),
        "port": int(env.get("DB_PORT", "3306") or 3306),
        "database": env.get("DB_NAME", "healthfirst"),
        "user": env.get("DB_USER", "root"),
        "password": env.get("DB_PASS", ""),
        "charset": "utf8mb4",
    }


def severity_from_counts(summary: dict[str, int]) -> str:
    if summary["Burns"] >= 2 or summary["Cuts & Bleeding"] >= 3:
        return "Critical"
    if summary["Burns"] >= 1 or summary["Cuts & Bleeding"] >= 2:
        return "Severe"
    if summary["Cuts & Bleeding"] >= 1:
        return "Moderate"
    return "Mild"


def fetch_sessions(connection, incident_id: int | None) -> list[dict]:
    where = "WHERE ins.report_file_path IS NOT NULL"
    params: tuple = ()
    if incident_id is not None:
        where += " AND ins.incident_id = %s"
        params = (incident_id,)

    query = f"""
        SELECT
            ins.session_id,
            ins.incident_id,
            ins.overall_severity,
            ins.special_note,
            ins.report_file_path,
            COALESCE(NULLIF(u.full_name, ''), NULLIF(i.reported_person_name, ''), 'Unknown Patient') AS patient_name
        FROM injury_sessions ins
        INNER JOIN accident_incidents i ON i.incident_id = ins.incident_id
        LEFT JOIN users u ON u.user_id = i.user_id
        {where}
        ORDER BY ins.session_id
    """

    cursor = connection.cursor(dictionary=True)
    cursor.execute(query, params)
    return list(cursor.fetchall())


def fetch_predictions(connection, session_id: int) -> list[dict]:
    cursor = connection.cursor(dictionary=True)
    cursor.execute(
        """
        SELECT predicted_label, confidence_score, burns_probability, cuts_probability, normal_probability
        FROM injury_image_predictions
        WHERE session_id = %s
        ORDER BY prediction_id ASC
        """,
        (session_id,),
    )
    predictions = []
    for row in cursor.fetchall():
        predictions.append(
            {
                "predicted_label": row.get("predicted_label") or "Normal (No Visible Injury)",
                "confidence": float(row.get("confidence_score") or 0),
                "probabilities": {
                    "Burns": float(row.get("burns_probability") or 0),
                    "Cuts & Bleeding": float(row.get("cuts_probability") or 0),
                    "Normal (No Visible Injury)": float(row.get("normal_probability") or 0),
                },
            }
        )
    return predictions


def regenerate_report(connection, session: dict) -> Path:
    predictions = fetch_predictions(connection, int(session["session_id"]))
    if not predictions:
        predictions = [
            {
                "predicted_label": "Normal (No Visible Injury)",
                "confidence": 100.0,
                "probabilities": {
                    "Burns": 0.0,
                    "Cuts & Bleeding": 0.0,
                    "Normal (No Visible Injury)": 100.0,
                },
            }
        ]

    summary = {label: 0 for label in LABELS}
    confidence_total = 0.0
    for prediction in predictions:
        label = prediction.get("predicted_label") or "Normal (No Visible Injury)"
        if label not in summary:
            label = "Normal (No Visible Injury)"
        summary[label] += 1
        confidence_total += float(prediction.get("confidence") or 0)

    total_images = max(len(predictions), 1)
    average_confidence = round(confidence_total / total_images, 2)
    severity = session.get("overall_severity") or severity_from_counts(summary)

    relative_path = str(session["report_file_path"]).replace("\\", "/").lstrip("/")
    output_path = BASE_DIR / "storage" / relative_path
    output_path.parent.mkdir(parents=True, exist_ok=True)

    build_injury_report(
        output_path=output_path,
        patient_name=str(session.get("patient_name") or "Unknown Patient"),
        incident_id=session.get("incident_id") or "N/A",
        severity=str(severity),
        summary_counts=summary,
        percentages={},
        special_note=session.get("special_note"),
        average_confidence=average_confidence,
        predictions=predictions,
    )

    cursor = connection.cursor()
    cursor.execute(
        "UPDATE injury_sessions SET summary_json = %s, overall_severity = %s WHERE session_id = %s",
        (
            json.dumps(
                {
                    "counts": summary,
                    "average_confidence": average_confidence,
                    "report_template": "clinical-evidence-v2",
                },
                ensure_ascii=False,
            ),
            severity,
            session["session_id"],
        ),
    )
    connection.commit()
    return output_path


def main() -> None:
    parser = argparse.ArgumentParser(description="Regenerate stored HealthFirst injury report PDFs with the current template.")
    parser.add_argument("--incident", type=int, help="Only regenerate reports for one incident id.")
    parser.add_argument("--all", action="store_true", help="Regenerate all finalized reports.")
    args = parser.parse_args()

    if not args.all and args.incident is None:
        parser.error("Use --all or --incident <id>.")

    connection = mysql.connector.connect(**db_config())
    try:
        sessions = fetch_sessions(connection, args.incident)
        for session in sessions:
            path = regenerate_report(connection, session)
            print(f"Regenerated incident {session['incident_id']} session {session['session_id']}: {path}")
        if not sessions:
            print("No stored injury reports found.")
    finally:
        connection.close()


if __name__ == "__main__":
    main()
