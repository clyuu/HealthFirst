import tensorflow as tf
from tensorflow.keras.preprocessing import image
import numpy as np

print("Loading the Accident Verification Model...")
model = tf.keras.models.load_model('custom_car_accident_model.keras')

# Provide the path to your test image here
img_path = 'img_00123.jpg' 

# Load and preprocess the image
img = image.load_img(img_path, target_size=(224, 224))
img_array = image.img_to_array(img)
img_array = img_array / 255.0  
img_array = np.expand_dims(img_array, axis=0)

print("Analyzing the image...")
prediction = model.predict(img_array)
result = prediction[0][0]

print("\n" + "="*50)
print(f"🧠 AI Raw Prediction Value: {result:.4f} (0 = Accident, 1 = Non-Accident)")

if result < 0.5:
    confidence = (1 - result) * 100
    print(f"⚠️ RESULT: REAL ACCIDENT DETECTED!")
    print(f"📊 Confidence Level: {confidence:.2f}%")
else:
    confidence = result * 100
    print(f"✅ RESULT: NORMAL VEHICLE (NON-ACCIDENT).")
    print(f"📊 Confidence Level: {confidence:.2f}%")
print("="*50 + "\n")