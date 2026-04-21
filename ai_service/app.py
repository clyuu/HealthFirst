from __future__ import annotations

from pathlib import Path
from threading import Lock
from uuid import uuid4

import numpy as np
import tensorflow as tf
from flask import Flask, jsonify, request
from PIL import Image

from utils.reporting import build_injury_report

BASE_DIR = Path(__file__).resolve().parents[1]
ACCIDENT_MODEL_PATH = BASE_DIR / "custom_car_accident_model.keras"
INJURY_MODEL_PATH = BASE_DIR / "custom_injury_model.keras"

ACCIDENT_MODEL = tf.keras.models.load_model(ACCIDENT_MODEL_PATH)
INJURY_MODEL = tf.keras.models.load_model(INJURY_MODEL_PATH)
CLASS_LABELS = {
    0: "Burns",
    1: "Cuts & Bleeding",
    2: "Normal (No Visible Injury)",
}

app = Flask(__name__)
sessions: dict[int, dict] = {}
session_lock = Lock()


def preprocess_image(file_storage) -> np.ndarray:
    image = Image.open(file_storage.stream).convert("RGB")
    image = image.resize((224, 224))
    array = np.asarray(image, dtype=np.float32) / 255.0
    return np.expand_dims(array, axis=0)


def ensure_session(session_id: int) -> dict:
    with session_lock:
        sessions.setdefault(session_id, {"token": uuid4().hex, "predictions": []})
        return sessions[session_id]


@app.get("/health")
def health() -> tuple:
    return jsonify(
        {
            "status": "ok",
            "accident_model": ACCIDENT_MODEL_PATH.name,
            "injury_model": INJURY_MODEL_PATH.name,
        }
    ), 200


@app.post("/accident/verify")
def verify_accident() -> tuple:
    image_file = request.files.get("image")
    if image_file is None:
        return jsonify({"error": "image is required"}), 422

    batch = preprocess_image(image_file)
    prediction = float(ACCIDENT_MODEL.predict(batch, verbose=0)[0][0])
    is_real_accident = prediction < 0.5
    confidence = (1 - prediction) * 100 if is_real_accident else prediction * 100

    return (
        jsonify(
            {
                "is_real_accident": is_real_accident,
                "raw_prediction": prediction,
                "confidence": round(confidence, 2),
                "model_version": "custom_car_accident_model.keras",
            }
        ),
        200,
    )


@app.post("/injury/sessions")
def start_injury_session() -> tuple:
    payload = request.get_json(silent=True) or {}
    session_hint = int(payload.get("incident_id", 0) or 0)
    token = uuid4().hex

    with session_lock:
        if session_hint > 0:
            sessions.setdefault(session_hint, {"token": token, "predictions": []})

    return jsonify({"session_token": token}), 201


@app.post("/injury/sessions/<int:session_id>/analyze")
def analyze_injury(session_id: int) -> tuple:
    image_file = request.files.get("image")
    if image_file is None:
        return jsonify({"error": "image is required"}), 422

    batch = preprocess_image(image_file)
    probabilities = INJURY_MODEL.predict(batch, verbose=0)[0]
    predicted_index = int(np.argmax(probabilities))
    predicted_label = CLASS_LABELS[predicted_index]
    confidence = float(probabilities[predicted_index] * 100)

    result = {
        "predicted_label": predicted_label,
        "confidence": round(confidence, 2),
        "probabilities": {
            CLASS_LABELS[0]: round(float(probabilities[0] * 100), 2),
            CLASS_LABELS[1]: round(float(probabilities[1] * 100), 2),
            CLASS_LABELS[2]: round(float(probabilities[2] * 100), 2),
        },
        "model_version": "custom_injury_model.keras",
    }

    session = ensure_session(session_id)
    session["predictions"].append(result)
    return jsonify(result), 200


@app.post("/injury/sessions/<int:session_id>/finalize")
def finalize_injury_session(session_id: int) -> tuple:
    payload = request.get_json(silent=True) or {}
    session = ensure_session(session_id)
    predictions = session.get("predictions", [])

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

    summary = {
        "Burns": 0,
        "Cuts & Bleeding": 0,
        "Normal (No Visible Injury)": 0,
    }
    total_confidence = 0.0

    for prediction in predictions:
        summary[prediction["predicted_label"]] += 1
        total_confidence += prediction["confidence"]

    total_images = len(predictions)
    percentages = {
        label: round((count / total_images) * 100, 2)
        for label, count in summary.items()
    }

    if summary["Burns"] >= 2 or summary["Cuts & Bleeding"] >= 3:
        severity = "Critical"
    elif summary["Burns"] >= 1 or summary["Cuts & Bleeding"] >= 2:
        severity = "Severe"
    elif summary["Cuts & Bleeding"] >= 1:
        severity = "Moderate"
    else:
        severity = "Mild"

    report_relative_path = f"generated/reports/injury-report-{session_id}.pdf"
    report_absolute_path = BASE_DIR / "storage" / report_relative_path
    report_absolute_path.parent.mkdir(parents=True, exist_ok=True)

    build_injury_report(
        output_path=report_absolute_path,
        patient_name=payload.get("patient_name", "Unknown Patient"),
        incident_id=payload.get("incident_id", "N/A"),
        severity=severity,
        summary_counts=summary,
        percentages=percentages,
        special_note=payload.get("special_note"),
        average_confidence=round(total_confidence / total_images, 2),
    )

    return (
        jsonify(
            {
                "overall_severity": severity,
                "summary": {
                    "counts": summary,
                    "percentages": percentages,
                    "average_confidence": round(total_confidence / total_images, 2),
                },
                "report_file_path": report_relative_path,
            }
        ),
        200,
    )


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5001, debug=True)

