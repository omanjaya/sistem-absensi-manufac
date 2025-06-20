#!/usr/bin/env python3
"""
Face Recognition Server for Attendance System
Python Flask API using face_recognition library
"""

import os
import json
import base64
import logging
import traceback
from datetime import datetime, timedelta
from io import BytesIO
import numpy as np
from PIL import Image
import face_recognition
from flask import Flask, request, jsonify
from flask_cors import CORS
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

app = Flask(__name__)
CORS(app)

# Configuration
app.config['FACE_DATA_DIR'] = os.getenv('FACE_DATA_DIR', './face_data')
app.config['LOG_LEVEL'] = os.getenv('LOG_LEVEL', 'INFO')
app.config['MAX_FACES_PER_USER'] = int(os.getenv('MAX_FACES_PER_USER', '5'))
app.config['RECOGNITION_TOLERANCE'] = float(os.getenv('RECOGNITION_TOLERANCE', '0.6'))
app.config['FACE_QUALITY_THRESHOLD'] = float(os.getenv('FACE_QUALITY_THRESHOLD', '0.8'))

# Setup logging
logging.basicConfig(
    level=getattr(logging, app.config['LOG_LEVEL']),
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('face_server.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Create face data directory if it doesn't exist
os.makedirs(app.config['FACE_DATA_DIR'], exist_ok=True)

class FaceRecognitionService:
    """Face recognition service with encoding storage and management"""
    
    def __init__(self, data_dir, tolerance=0.6):
        self.data_dir = data_dir
        self.tolerance = tolerance
        self.known_encodings = {}
        self.load_known_faces()
    
    def load_known_faces(self):
        """Load all stored face encodings from files"""
        self.known_encodings = {}
        
        try:
            for filename in os.listdir(self.data_dir):
                if filename.endswith('.npy'):
                    user_id = filename.replace('.npy', '')
                    file_path = os.path.join(self.data_dir, filename)
                    
                    try:
                        encoding = np.load(file_path)
                        self.known_encodings[user_id] = encoding
                        logger.info(f"Loaded face encoding for user {user_id}")
                    except Exception as e:
                        logger.error(f"Error loading encoding for user {user_id}: {e}")
                        
            logger.info(f"Loaded {len(self.known_encodings)} face encodings")
        except Exception as e:
            logger.error(f"Error loading known faces: {e}")
    
    def decode_base64_image(self, base64_string):
        """Decode base64 image string to PIL Image"""
        try:
            # Remove data URL prefix if present
            if ',' in base64_string:
                base64_string = base64_string.split(',')[1]
            
            # Decode base64
            image_data = base64.b64decode(base64_string)
            image = Image.open(BytesIO(image_data))
            
            # Convert to RGB if needed
            if image.mode != 'RGB':
                image = image.convert('RGB')
            
            return np.array(image)
        except Exception as e:
            logger.error(f"Error decoding base64 image: {e}")
            return None
    
    def detect_faces(self, image_array):
        """Detect faces in image and return face locations and encodings"""
        try:
            # Find face locations
            face_locations = face_recognition.face_locations(image_array)
            
            if not face_locations:
                return None, "No face detected in image"
            
            if len(face_locations) > 1:
                return None, "Multiple faces detected. Please ensure only one face is visible"
            
            # Get face encodings
            face_encodings = face_recognition.face_encodings(image_array, face_locations)
            
            if not face_encodings:
                return None, "Could not generate face encoding"
            
            return face_encodings[0], None
        except Exception as e:
            logger.error(f"Error detecting faces: {e}")
            return None, f"Face detection error: {str(e)}"
    
    def register_face(self, user_id, image_array):
        """Register a new face encoding for a user"""
        try:
            encoding, error = self.detect_faces(image_array)
            if error:
                return False, error
            
            # Save encoding to file
            file_path = os.path.join(self.data_dir, f"{user_id}.npy")
            np.save(file_path, encoding)
            
            # Update in-memory storage
            self.known_encodings[str(user_id)] = encoding
            
            # Save metadata
            metadata = {
                'user_id': user_id,
                'registered_at': datetime.now().isoformat(),
                'encoding_shape': encoding.shape,
                'encoding_count': 1
            }
            
            metadata_path = os.path.join(self.data_dir, f"{user_id}_meta.json")
            with open(metadata_path, 'w') as f:
                json.dump(metadata, f)
            
            logger.info(f"Face registered successfully for user {user_id}")
            return True, "Face registered successfully"
            
        except Exception as e:
            logger.error(f"Error registering face for user {user_id}: {e}")
            return False, f"Registration error: {str(e)}"
    
    def recognize_face(self, image_array):
        """Recognize a face in the image"""
        try:
            if not self.known_encodings:
                return None, 0.0, "No registered faces found"
            
            encoding, error = self.detect_faces(image_array)
            if error:
                return None, 0.0, error
            
            # Compare with known faces
            best_match_user = None
            best_confidence = 0.0
            
            for user_id, known_encoding in self.known_encodings.items():
                # Calculate face distance (lower is better)
                face_distance = face_recognition.face_distance([known_encoding], encoding)[0]
                
                # Convert to confidence (higher is better)
                confidence = 1 - face_distance
                
                # Check if it's a match and better than previous matches
                if face_distance <= self.tolerance and confidence > best_confidence:
                    best_match_user = user_id
                    best_confidence = confidence
            
            if best_match_user:
                logger.info(f"Face recognized: user {best_match_user} with confidence {best_confidence:.2f}")
                return best_match_user, best_confidence, "Face recognized successfully"
            else:
                return None, 0.0, "Face not recognized"
                
        except Exception as e:
            logger.error(f"Error recognizing face: {e}")
            return None, 0.0, f"Recognition error: {str(e)}"
    
    def get_user_status(self, user_id):
        """Get registration status for a user"""
        file_path = os.path.join(self.data_dir, f"{user_id}.npy")
        metadata_path = os.path.join(self.data_dir, f"{user_id}_meta.json")
        
        if os.path.exists(file_path):
            metadata = {}
            if os.path.exists(metadata_path):
                try:
                    with open(metadata_path, 'r') as f:
                        metadata = json.load(f)
                except Exception as e:
                    logger.error(f"Error reading metadata for user {user_id}: {e}")
            
            return {
                'registered': True,
                'last_updated': metadata.get('registered_at'),
                'encoding_count': metadata.get('encoding_count', 1)
            }
        else:
            return {
                'registered': False,
                'last_updated': None,
                'encoding_count': 0
            }
    
    def delete_user_face(self, user_id):
        """Delete face data for a user"""
        try:
            file_path = os.path.join(self.data_dir, f"{user_id}.npy")
            metadata_path = os.path.join(self.data_dir, f"{user_id}_meta.json")
            
            deleted_files = []
            
            if os.path.exists(file_path):
                os.remove(file_path)
                deleted_files.append(file_path)
            
            if os.path.exists(metadata_path):
                os.remove(metadata_path)
                deleted_files.append(metadata_path)
            
            # Remove from memory
            if str(user_id) in self.known_encodings:
                del self.known_encodings[str(user_id)]
            
            if deleted_files:
                logger.info(f"Deleted face data for user {user_id}: {deleted_files}")
                return True, f"Face data deleted successfully"
            else:
                return False, "No face data found for user"
                
        except Exception as e:
            logger.error(f"Error deleting face data for user {user_id}: {e}")
            return False, f"Deletion error: {str(e)}"

# Initialize face recognition service
face_service = FaceRecognitionService(
    app.config['FACE_DATA_DIR'], 
    app.config['RECOGNITION_TOLERANCE']
)

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'online',
        'service': 'Face Recognition Server',
        'version': '1.0.0',
        'timestamp': datetime.now().isoformat(),
        'registered_faces': len(face_service.known_encodings),
        'face_data_dir': app.config['FACE_DATA_DIR'],
        'recognition_tolerance': app.config['RECOGNITION_TOLERANCE']
    })

@app.route('/register-face', methods=['POST'])
def register_face():
    """Register a new face for a user"""
    try:
        data = request.get_json()
        
        if not data or 'user_id' not in data or 'photo' not in data:
            return jsonify({
                'success': False,
                'message': 'Missing required fields: user_id and photo'
            }), 400
        
        user_id = data['user_id']
        photo_base64 = data['photo']
        
        # Decode image
        image_array = face_service.decode_base64_image(photo_base64)
        if image_array is None:
            return jsonify({
                'success': False,
                'message': 'Invalid image format'
            }), 400
        
        # Register face
        success, message = face_service.register_face(user_id, image_array)
        
        if success:
            return jsonify({
                'success': True,
                'message': message,
                'user_id': user_id,
                'registered_at': datetime.now().isoformat()
            })
        else:
            return jsonify({
                'success': False,
                'message': message
            }), 400
            
    except Exception as e:
        logger.error(f"Error in register_face endpoint: {traceback.format_exc()}")
        return jsonify({
            'success': False,
            'message': f'Server error: {str(e)}'
        }), 500

@app.route('/recognize', methods=['POST'])
def recognize_face():
    """Recognize a face in the provided image"""
    try:
        data = request.get_json()
        
        if not data or 'photo' not in data:
            return jsonify({
                'success': False,
                'message': 'Missing required field: photo'
            }), 400
        
        photo_base64 = data['photo']
        
        # Decode image
        image_array = face_service.decode_base64_image(photo_base64)
        if image_array is None:
            return jsonify({
                'success': False,
                'message': 'Invalid image format'
            }), 400
        
        # Recognize face
        user_id, confidence, message = face_service.recognize_face(image_array)
        
        if user_id:
            return jsonify({
                'success': True,
                'message': message,
                'user_id': int(user_id),
                'confidence': round(confidence, 4),
                'recognized_at': datetime.now().isoformat()
            })
        else:
            return jsonify({
                'success': False,
                'message': message,
                'user_id': 'unknown',
                'confidence': 0.0
            }), 404
            
    except Exception as e:
        logger.error(f"Error in recognize endpoint: {traceback.format_exc()}")
        return jsonify({
            'success': False,
            'message': f'Server error: {str(e)}'
        }), 500

@app.route('/status/<int:user_id>', methods=['GET'])
def get_face_status(user_id):
    """Get face registration status for a specific user"""
    try:
        status = face_service.get_user_status(user_id)
        return jsonify({
            'success': True,
            'user_id': user_id,
            **status
        })
        
    except Exception as e:
        logger.error(f"Error in status endpoint: {e}")
        return jsonify({
            'success': False,
            'message': f'Server error: {str(e)}'
        }), 500

@app.route('/face/<int:user_id>', methods=['DELETE'])
def delete_face(user_id):
    """Delete face data for a specific user"""
    try:
        success, message = face_service.delete_user_face(user_id)
        
        if success:
            return jsonify({
                'success': True,
                'message': message,
                'user_id': user_id
            })
        else:
            return jsonify({
                'success': False,
                'message': message
            }), 404
            
    except Exception as e:
        logger.error(f"Error in delete_face endpoint: {e}")
        return jsonify({
            'success': False,
            'message': f'Server error: {str(e)}'
        }), 500

@app.route('/stats', methods=['GET'])
def get_stats():
    """Get server statistics"""
    try:
        import psutil
        
        return jsonify({
            'success': True,
            'stats': {
                'registered_users': len(face_service.known_encodings),
                'total_encodings': len(face_service.known_encodings),
                'recognition_tolerance': app.config['RECOGNITION_TOLERANCE'],
                'data_directory': app.config['FACE_DATA_DIR'],
                'server_info': {
                    'cpu_percent': psutil.cpu_percent(),
                    'memory_percent': psutil.virtual_memory().percent,
                    'disk_usage': psutil.disk_usage('/').percent
                }
            }
        })
        
    except Exception as e:
        logger.error(f"Error in stats endpoint: {e}")
        return jsonify({
            'success': False,
            'message': f'Server error: {str(e)}'
        }), 500

@app.errorhandler(404)
def not_found(error):
    return jsonify({
        'success': False,
        'message': 'Endpoint not found'
    }), 404

@app.errorhandler(500)
def internal_error(error):
    return jsonify({
        'success': False,
        'message': 'Internal server error'
    }), 500

if __name__ == '__main__':
    logger.info("Starting Face Recognition Server...")
    logger.info(f"Face data directory: {app.config['FACE_DATA_DIR']}")
    logger.info(f"Recognition tolerance: {app.config['RECOGNITION_TOLERANCE']}")
    logger.info(f"Loaded {len(face_service.known_encodings)} face encodings")
    
    # Run the Flask app
    app.run(
        host=os.getenv('FLASK_HOST', '0.0.0.0'),
        port=int(os.getenv('FLASK_PORT', 5000)),
        debug=os.getenv('FLASK_DEBUG', 'False').lower() == 'true'
    ) 