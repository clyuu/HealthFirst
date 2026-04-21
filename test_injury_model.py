import tensorflow as tf
from tensorflow.keras.preprocessing import image
import numpy as np

print("Loading the Custom Injury Detection Model...")
# Load the saved multi-class model
model = tf.keras.models.load_model('custom_injury_model.keras')

# Define the exact class labels based on alphabetical folder order
class_labels = {
    0: "Burns",
    1: "Cuts & Bleeding",
    2: "Normal (No Visible Injury)"
}

# Provide the path to your test image here
img_path = '22.jpg' 

# Load and preprocess the image (Must match training preprocessing)
img = image.load_img(img_path, target_size=(224, 224))
img_array = image.img_to_array(img)
img_array = img_array / 255.0  
img_array = np.expand_dims(img_array, axis=0)

print("Analyzing the injury image...")
# Get the prediction array (Contains probabilities for all 3 classes)
predictions = model.predict(img_array)[0]

# Find the index of the highest probability
predicted_class_index = np.argmax(predictions)
predicted_class_name = class_labels[predicted_class_index]
confidence = predictions[predicted_class_index] * 100

print("\n" + "="*50)
print(f"🧠 AI Raw Probabilities (Softmax Output):")
print(f" - Burns:           {predictions[0]*100:.2f}%")
print(f" - Cuts & Bleeding: {predictions[1]*100:.2f}%")
print(f" - Normal:          {predictions[2]*100:.2f}%")
print("-" * 50)

# Display the final categorized result
if predicted_class_index == 2:
    print(f"✅ FINAL RESULT: {predicted_class_name}")
else:
    print(f"⚠️ FINAL RESULT: {predicted_class_name} DETECTED!")
    
print(f"📊 Confidence Level: {confidence:.2f}%")
print("="*50 + "\n")