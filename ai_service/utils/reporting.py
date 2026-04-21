from __future__ import annotations

from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet
from reportlab.platypus import Paragraph, SimpleDocTemplate, Spacer, Table, TableStyle


def build_injury_report(
    output_path: Path,
    patient_name: str,
    incident_id: str | int,
    severity: str,
    summary_counts: dict,
    percentages: dict,
    special_note: str | None,
    average_confidence: float,
) -> None:
    doc = SimpleDocTemplate(str(output_path), pagesize=A4, rightMargin=36, leftMargin=36, topMargin=36, bottomMargin=36)
    styles = getSampleStyleSheet()
    story = []

    title = Paragraph("HealthFirst AI Preliminary Injury Report", styles["Title"])
    story.append(title)
    story.append(Spacer(1, 12))

    summary_table = Table(
        [
            ["Patient", patient_name],
            ["Incident ID", str(incident_id)],
            ["AI Severity", severity],
            ["Average Confidence", f"{average_confidence:.2f}%"],
            ["Special Note", special_note or "None provided"],
        ],
        colWidths=[140, 340],
    )
    summary_table.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, 0), colors.whitesmoke),
                ("GRID", (0, 0), (-1, -1), 0.5, colors.grey),
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ("FONTNAME", (0, 0), (-1, 0), "Helvetica-Bold"),
            ]
        )
    )
    story.append(summary_table)
    story.append(Spacer(1, 18))

    rows = [["Detected Type", "Image Count", "Distribution %"]]
    for label in ["Burns", "Cuts & Bleeding", "Normal (No Visible Injury)"]:
        rows.append([label, str(summary_counts.get(label, 0)), f"{percentages.get(label, 0):.2f}%"])

    detection_table = Table(rows, colWidths=[240, 120, 120])
    detection_table.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#d14343")),
                ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
                ("GRID", (0, 0), (-1, -1), 0.5, colors.grey),
                ("FONTNAME", (0, 0), (-1, 0), "Helvetica-Bold"),
                ("BACKGROUND", (0, 1), (-1, -1), colors.whitesmoke),
            ]
        )
    )
    story.append(detection_table)
    story.append(Spacer(1, 18))

    notes = Paragraph(
        "This PDF is generated automatically from uploaded ambulance injury photos using the "
        "custom_injury_model.keras classifier. It is an AI preliminary assessment only and must "
        "not replace clinical judgement.",
        styles["BodyText"],
    )
    story.append(notes)

    doc.build(story)

