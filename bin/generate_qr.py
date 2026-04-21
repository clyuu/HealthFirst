import argparse
from pathlib import Path

import qrcode


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--text", required=True)
    parser.add_argument("--output", required=True)
    args = parser.parse_args()

    output = Path(args.output)
    output.parent.mkdir(parents=True, exist_ok=True)

    qr = qrcode.QRCode(version=3, box_size=10, border=2)
    qr.add_data(args.text)
    qr.make(fit=True)
    image = qr.make_image(fill_color="black", back_color="white")
    image.save(output)


if __name__ == "__main__":
    main()

