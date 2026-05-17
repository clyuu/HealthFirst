from __future__ import annotations

from datetime import datetime
from pathlib import Path
from xml.sax.saxutils import escape

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.platypus import (
    KeepTogether,
    Paragraph,
    SimpleDocTemplate,
    Spacer,
    Table,
    TableStyle,
)


LABELS = ["Burns", "Cuts & Bleeding", "Normal (No Visible Injury)"]
CLINICAL_WEIGHTS = {
    "Burns": 3,
    "Cuts & Bleeding": 2,
    "Normal (No Visible Injury)": 0,
}
SEVERITY_COLORS = {
    "Mild": "#159a64",
    "Moderate": "#d28b00",
    "Severe": "#d24b2f",
    "Critical": "#9b1c31",
}


def _safe(value: object) -> str:
    text = str(value if value is not None else "").strip()
    return escape(text or "-")


def _p(text: object, style: ParagraphStyle) -> Paragraph:
    return Paragraph(_safe(text), style)


def _assessment(summary_counts: dict, total_images: int, severity: str) -> dict:
    burns = int(summary_counts.get("Burns", 0) or 0)
    cuts = int(summary_counts.get("Cuts & Bleeding", 0) or 0)
    normal = int(summary_counts.get("Normal (No Visible Injury)", 0) or 0)
    injury_findings = burns + cuts
    injury_score = (burns * CLINICAL_WEIGHTS["Burns"]) + (cuts * CLINICAL_WEIGHTS["Cuts & Bleeding"])

    if injury_score >= 9:
        damage_estimate = "Very high localized injury burden"
        triage_priority = "Immediate emergency care"
    elif injury_score >= 6:
        damage_estimate = "High localized injury burden"
        triage_priority = "Urgent trauma or burn review"
    elif injury_score >= 3:
        damage_estimate = "Moderate localized injury burden"
        triage_priority = "Prompt clinical assessment"
    elif injury_score > 0:
        damage_estimate = "Low localized injury burden"
        triage_priority = "Clinical assessment recommended"
    else:
        damage_estimate = "No visible injury detected in uploaded photos"
        triage_priority = "Reassess if symptoms or mechanism are concerning"

    outlook = {
        "Critical": "Critical concern - survivability depends on vitals, bleeding control, airway, burn depth, and rapid treatment.",
        "Severe": "Guarded - serious visible injury evidence; urgent transport and clinician review are recommended.",
        "Moderate": "Generally favorable if vitals are stable, but visible injury needs timely assessment.",
        "Mild": "Favorable from uploaded image evidence only, assuming vitals and symptoms are stable.",
    }.get(severity, "Requires clinician assessment.")

    limitation = (
        "This is not a whole-body damage percentage. Uploaded photos usually show only selected wound areas, "
        "so the AI cannot estimate total body surface area, internal injury, blood loss, shock, airway risk, "
        "or a numeric survival probability from images alone."
    )

    return {
        "injury_findings": injury_findings,
        "normal_findings": normal,
        "total_images": total_images,
        "injury_score": injury_score,
        "damage_estimate": damage_estimate,
        "triage_priority": triage_priority,
        "survivability_outlook": outlook,
        "limitation": limitation,
    }


def _build_styles() -> dict:
    base = getSampleStyleSheet()
    return {
        "title": ParagraphStyle(
            "HealthFirstTitle",
            parent=base["Title"],
            fontName="Helvetica-Bold",
            fontSize=21,
            leading=25,
            alignment=TA_CENTER,
            textColor=colors.HexColor("#10243f"),
            spaceAfter=6,
        ),
        "subtitle": ParagraphStyle(
            "HealthFirstSubtitle",
            parent=base["BodyText"],
            fontName="Helvetica",
            fontSize=9,
            leading=12,
            alignment=TA_CENTER,
            textColor=colors.HexColor("#607089"),
        ),
        "section": ParagraphStyle(
            "HealthFirstSection",
            parent=base["Heading2"],
            fontName="Helvetica-Bold",
            fontSize=12,
            leading=15,
            textColor=colors.HexColor("#10243f"),
            spaceBefore=10,
            spaceAfter=7,
        ),
        "body": ParagraphStyle(
            "HealthFirstBody",
            parent=base["BodyText"],
            fontName="Helvetica",
            fontSize=9,
            leading=12,
            textColor=colors.HexColor("#243047"),
        ),
        "small": ParagraphStyle(
            "HealthFirstSmall",
            parent=base["BodyText"],
            fontName="Helvetica",
            fontSize=8,
            leading=10,
            textColor=colors.HexColor("#607089"),
        ),
        "card_label": ParagraphStyle(
            "HealthFirstCardLabel",
            parent=base["BodyText"],
            fontName="Helvetica-Bold",
            fontSize=7,
            leading=9,
            textColor=colors.HexColor("#607089"),
        ),
        "card_value": ParagraphStyle(
            "HealthFirstCardValue",
            parent=base["BodyText"],
            fontName="Helvetica-Bold",
            fontSize=12,
            leading=14,
            textColor=colors.HexColor("#10243f"),
        ),
        "callout": ParagraphStyle(
            "HealthFirstCallout",
            parent=base["BodyText"],
            fontName="Helvetica-Bold",
            fontSize=9,
            leading=12,
            textColor=colors.HexColor("#6f3213"),
        ),
    }


def _footer(canvas, doc) -> None:
    canvas.saveState()
    canvas.setStrokeColor(colors.HexColor("#d7dfec"))
    canvas.line(doc.leftMargin, 18 * mm, A4[0] - doc.rightMargin, 18 * mm)
    canvas.setFont("Helvetica", 7)
    canvas.setFillColor(colors.HexColor("#7a8799"))
    canvas.drawString(doc.leftMargin, 12 * mm, "HealthFirst AI preliminary report - clinician review required")
    canvas.drawRightString(A4[0] - doc.rightMargin, 12 * mm, f"Page {doc.page}")
    canvas.restoreState()


def _summary_card(label: str, value: str, styles: dict, value_color: str = "#10243f") -> Table:
    value_style = ParagraphStyle(
        f"CardValue{label}",
        parent=styles["card_value"],
        textColor=colors.HexColor(value_color),
    )
    table = Table(
        [[_p(label.upper(), styles["card_label"])], [_p(value, value_style)]],
        colWidths=[118],
        rowHeights=[15, 30],
    )
    table.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, -1), colors.HexColor("#f6f9fd")),
                ("BOX", (0, 0), (-1, -1), 0.6, colors.HexColor("#d7dfec")),
                ("LEFTPADDING", (0, 0), (-1, -1), 10),
                ("RIGHTPADDING", (0, 0), (-1, -1), 10),
                ("TOPPADDING", (0, 0), (-1, -1), 6),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
                ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
            ]
        )
    )
    return table


def build_injury_report(
    output_path: Path,
    patient_name: str,
    incident_id: str | int,
    severity: str,
    summary_counts: dict,
    percentages: dict | None,
    special_note: str | None,
    average_confidence: float,
    predictions: list[dict] | None = None,
) -> None:
    del percentages
    predictions = predictions or []
    total_images = max(sum(int(summary_counts.get(label, 0) or 0) for label in LABELS), len(predictions), 1)
    assessment = _assessment(summary_counts, total_images, severity)
    styles = _build_styles()

    doc = SimpleDocTemplate(
        str(output_path),
        pagesize=A4,
        rightMargin=34,
        leftMargin=34,
        topMargin=32,
        bottomMargin=34,
    )
    story = []

    story.append(_p("HealthFirst AI Preliminary Injury Report", styles["title"]))
    story.append(
        _p(
            f"Evidence-based wound photo summary generated {datetime.now().strftime('%Y-%m-%d %H:%M')}",
            styles["subtitle"],
        )
    )
    story.append(Spacer(1, 12))

    severity_color = SEVERITY_COLORS.get(severity, "#10243f")
    cards = Table(
        [
            [
                _summary_card("Overall AI severity", severity, styles, severity_color),
                _summary_card("Uploaded photos", str(total_images), styles),
                _summary_card("Injury findings", str(assessment["injury_findings"]), styles, "#d24b2f"),
                _summary_card("AI confidence", f"{average_confidence:.1f}%", styles),
            ]
        ],
        colWidths=[126, 126, 126, 126],
    )
    cards.setStyle(TableStyle([("VALIGN", (0, 0), (-1, -1), "TOP")]))
    story.append(cards)
    story.append(Spacer(1, 12))

    patient_table = Table(
        [
            [_p("Patient", styles["card_label"]), _p(patient_name, styles["body"])],
            [_p("Incident ID", styles["card_label"]), _p(incident_id, styles["body"])],
            [_p("Special note", styles["card_label"]), _p(special_note or "None provided", styles["body"])],
        ],
        colWidths=[95, 409],
    )
    patient_table.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (0, -1), colors.HexColor("#eef4fb")),
                ("BACKGROUND", (1, 0), (1, -1), colors.white),
                ("BOX", (0, 0), (-1, -1), 0.6, colors.HexColor("#d7dfec")),
                ("INNERGRID", (0, 0), (-1, -1), 0.4, colors.HexColor("#d7dfec")),
                ("LEFTPADDING", (0, 0), (-1, -1), 8),
                ("RIGHTPADDING", (0, 0), (-1, -1), 8),
                ("TOPPADDING", (0, 0), (-1, -1), 6),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
            ]
        )
    )
    story.append(patient_table)

    story.append(_p("AI Assessment", styles["section"]))
    assessment_table = Table(
        [
            [_p("Localized damage estimate", styles["card_label"]), _p(assessment["damage_estimate"], styles["body"])],
            [_p("Survivability outlook", styles["card_label"]), _p(assessment["survivability_outlook"], styles["body"])],
            [_p("Triage priority", styles["card_label"]), _p(assessment["triage_priority"], styles["body"])],
        ],
        colWidths=[150, 354],
    )
    assessment_table.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (0, -1), colors.HexColor("#fff4ec")),
                ("BOX", (0, 0), (-1, -1), 0.6, colors.HexColor("#efc5ad")),
                ("INNERGRID", (0, 0), (-1, -1), 0.4, colors.HexColor("#efc5ad")),
                ("LEFTPADDING", (0, 0), (-1, -1), 8),
                ("RIGHTPADDING", (0, 0), (-1, -1), 8),
                ("TOPPADDING", (0, 0), (-1, -1), 7),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 7),
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
            ]
        )
    )
    story.append(assessment_table)

    story.append(Spacer(1, 8))
    limitation_box = Table(
        [[_p("Important limitation", styles["callout"])], [_p(assessment["limitation"], styles["body"])]],
        colWidths=[504],
    )
    limitation_box.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, -1), colors.HexColor("#fff8e6")),
                ("BOX", (0, 0), (-1, -1), 0.8, colors.HexColor("#e2b45b")),
                ("LEFTPADDING", (0, 0), (-1, -1), 10),
                ("RIGHTPADDING", (0, 0), (-1, -1), 10),
                ("TOPPADDING", (0, 0), (-1, -1), 7),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 7),
            ]
        )
    )
    story.append(limitation_box)

    story.append(_p("Detected Injury Evidence", styles["section"]))
    evidence_rows = [[_p("Finding type", styles["card_label"]), _p("Photo evidence count", styles["card_label"]), _p("Clinical weight", styles["card_label"]), _p("Interpretation", styles["card_label"])]]
    interpretations = {
        "Burns": "Visible burn-like pattern in uploaded wound photos.",
        "Cuts & Bleeding": "Visible cut, bleeding, or laceration-like pattern.",
        "Normal (No Visible Injury)": "Uploaded image did not show a clear visible injury pattern.",
    }
    for label in LABELS:
        count = int(summary_counts.get(label, 0) or 0)
        weight = CLINICAL_WEIGHTS[label]
        evidence_rows.append([
            _p(label, styles["body"]),
            _p(str(count), styles["body"]),
            _p(str(weight), styles["body"]),
            _p(interpretations[label], styles["small"]),
        ])

    evidence_table = Table(evidence_rows, colWidths=[150, 95, 80, 179], repeatRows=1)
    evidence_table.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#10243f")),
                ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
                ("BACKGROUND", (0, 1), (-1, -1), colors.HexColor("#f8fbff")),
                ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.HexColor("#f8fbff"), colors.white]),
                ("BOX", (0, 0), (-1, -1), 0.6, colors.HexColor("#d7dfec")),
                ("INNERGRID", (0, 0), (-1, -1), 0.4, colors.HexColor("#d7dfec")),
                ("LEFTPADDING", (0, 0), (-1, -1), 7),
                ("RIGHTPADDING", (0, 0), (-1, -1), 7),
                ("TOPPADDING", (0, 0), (-1, -1), 6),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
            ]
        )
    )
    story.append(evidence_table)

    if predictions:
        story.append(_p("Per-Image AI Findings", styles["section"]))
        detail_rows = [[_p("Photo", styles["card_label"]), _p("Primary finding", styles["card_label"]), _p("AI confidence", styles["card_label"])]]
        for index, prediction in enumerate(predictions, start=1):
            confidence = float(prediction.get("confidence", prediction.get("confidence_score", 0)) or 0)
            detail_rows.append([
                _p(f"Image {index}", styles["body"]),
                _p(prediction.get("predicted_label", "Unknown"), styles["body"]),
                _p(f"{confidence:.1f}%", styles["body"]),
            ])

        detail_table = Table(detail_rows, colWidths=[95, 285, 124], repeatRows=1)
        detail_table.setStyle(
            TableStyle(
                [
                    ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#eaf1fb")),
                    ("BOX", (0, 0), (-1, -1), 0.6, colors.HexColor("#d7dfec")),
                    ("INNERGRID", (0, 0), (-1, -1), 0.4, colors.HexColor("#d7dfec")),
                    ("LEFTPADDING", (0, 0), (-1, -1), 7),
                    ("RIGHTPADDING", (0, 0), (-1, -1), 7),
                    ("TOPPADDING", (0, 0), (-1, -1), 6),
                    ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
                    ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ]
            )
        )
        story.append(detail_table)

    checklist = [
        "Confirm airway, breathing, circulation, mental status, and vital signs.",
        "Control active bleeding, cool burns as clinically appropriate, and protect wounds from contamination.",
        "Assess pain, shock signs, fracture risk, burn depth, and injuries outside the photographed area.",
        "Use this AI report only as supporting evidence for ambulance and hospital handover.",
    ]
    story.append(
        KeepTogether(
            [
                _p("Immediate Clinical Checklist", styles["section"]),
                Table(
                    [[_p(item, styles["body"])] for item in checklist],
                    colWidths=[504],
                    style=TableStyle(
                        [
                            ("BACKGROUND", (0, 0), (-1, -1), colors.HexColor("#f6f9fd")),
                            ("BOX", (0, 0), (-1, -1), 0.6, colors.HexColor("#d7dfec")),
                            ("INNERGRID", (0, 0), (-1, -1), 0.4, colors.HexColor("#d7dfec")),
                            ("LEFTPADDING", (0, 0), (-1, -1), 9),
                            ("RIGHTPADDING", (0, 0), (-1, -1), 9),
                            ("TOPPADDING", (0, 0), (-1, -1), 6),
                            ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
                        ]
                    ),
                )
            ]
        )
    )

    doc.build(story, onFirstPage=_footer, onLaterPages=_footer)
