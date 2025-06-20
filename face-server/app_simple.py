#!/usr/bin/env python3
"""
Simplified Face Recognition Server
Alternative version without complex face_recognition library for Windows compatibility
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import os
import base64
import json
import time
import random
from datetime import datetime

app = Flask(__name__)
CORS(app)

# Configuration
FACES_DIR = 'faces'
if not os.path.exists(FACES_DIR):
    os.makedirs(FACES_DIR)

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'message': 'Face Recognition Service is running',
        'timestamp': datetime.now().isoformat(),
        'version': '1.0.0-simple'
    })

@app.route('/register-face', methods=['POST'])
def register_face():
    """Register a new face (simplified version)"""
    try:
        data = request.get_json()
        
        if not data or 'employee_id' not in data or 'photo' not in data:
            return jsonify({
                'success': False,
                'message': 'Missing employee_id or photo'
            }), 400
        
        employee_id = data['employee_id']
        photo_data = data['photo']
        name = data.get('name', f'Employee {employee_id}')
        
        # Remove data URL prefix if present
        if photo_data.startswith('data:image'):
            photo_data = photo_data.split(',')[1]
        
        # Save photo as base64 encoded file
        face_file = os.path.join(FACES_DIR, f'{employee_id}.json')
        face_data = {
            'employee_id': employee_id,
            'name': name,
            'photo': photo_data,
            'registered_at': datetime.now().isoformat(),
            'version': '1.0'
        }
        
        with open(face_file, 'w') as f:
            json.dump(face_data, f)
        
        return jsonify({
            'success': True,
            'message': f'Face registered successfully for {name}',
            'employee_id': employee_id,
            'registered_at': face_data['registered_at']
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Registration failed: {str(e)}'
        }), 500

@app.route('/recognize', methods=['POST'])
def recognize_face():
    """Recognize a face (simplified version with mock recognition)"""
    try:
        data = request.get_json()
        
        if not data or 'photo' not in data:
            return jsonify({
                'success': False,
                'message': 'Missing photo data'
            }), 400
        
        photo_data = data['photo']
        
        # Remove data URL prefix if present
        if photo_data.startswith('data:image'):
            photo_data = photo_data.split(',')[1]
        
        # Get all registered faces
        registered_faces = []
        for filename in os.listdir(FACES_DIR):
            if filename.endswith('.json'):
                face_file = os.path.join(FACES_DIR, filename)
                with open(face_file, 'r') as f:
                    face_data = json.load(f)
                    registered_faces.append(face_data)
        
        if not registered_faces:
            return jsonify({
                'success': False,
                'message': 'No registered faces found'
            }), 404
        
        # Simplified recognition (mock implementation)
        # In real implementation, this would use actual face recognition algorithms
        # For demo purposes, we'll randomly select a registered face with high confidence
        
        if len(registered_faces) > 0:
            # Simulate recognition process
            time.sleep(0.5)  # Simulate processing time
            
            # For demo: randomly succeed or fail recognition
            recognition_success = random.random() > 0.3  # 70% success rate
            
            if recognition_success:
                # Select most recently registered face for demo
                recognized_face = max(registered_faces, key=lambda x: x['registered_at'])
                confidence = round(random.uniform(0.85, 0.98), 3)  # High confidence
                
                return jsonify({
                    'success': True,
                    'recognized': True,
                    'employee_id': recognized_face['employee_id'],
                    'name': recognized_face['name'],
                    'confidence': confidence,
                    'message': f'Face recognized: {recognized_face["name"]}',
                    'timestamp': datetime.now().isoformat()
                })
            else:
                return jsonify({
                    'success': True,
                    'recognized': False,
                    'message': 'Face not recognized',
                    'confidence': round(random.uniform(0.1, 0.4), 3),  # Low confidence
                    'timestamp': datetime.now().isoformat()
                })
        
        return jsonify({
            'success': False,
            'message': 'No faces to compare against'
        }), 404
        
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Recognition failed: {str(e)}'
        }), 500

@app.route('/faces', methods=['GET'])
def list_faces():
    """List all registered faces"""
    try:
        faces = []
        for filename in os.listdir(FACES_DIR):
            if filename.endswith('.json'):
                face_file = os.path.join(FACES_DIR, filename)
                with open(face_file, 'r') as f:
                    face_data = json.load(f)
                    # Don't include photo data in list
                    faces.append({
                        'employee_id': face_data['employee_id'],
                        'name': face_data['name'],
                        'registered_at': face_data['registered_at']
                    })
        
        return jsonify({
            'success': True,
            'total': len(faces),
            'faces': faces
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Failed to list faces: {str(e)}'
        }), 500

@app.route('/faces/<employee_id>', methods=['DELETE'])
def delete_face(employee_id):
    """Delete a registered face"""
    try:
        face_file = os.path.join(FACES_DIR, f'{employee_id}.json')
        
        if not os.path.exists(face_file):
            return jsonify({
                'success': False,
                'message': 'Face not found'
            }), 404
        
        os.remove(face_file)
        
        return jsonify({
            'success': True,
            'message': f'Face deleted for employee {employee_id}'
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Failed to delete face: {str(e)}'
        }), 500

@app.route('/', methods=['GET'])
def index():
    """Index page with API info"""
    return jsonify({
        'name': 'Face Recognition Service (Simplified)',
        'version': '1.0.0-simple',
        'status': 'running',
        'note': 'This is a simplified version without complex face recognition dependencies',
        'endpoints': {
            'health': '/health',
            'register': '/register-face',
            'recognize': '/recognize',
            'list': '/faces',
            'delete': '/faces/<employee_id>'
        },
        'timestamp': datetime.now().isoformat()
    })

if __name__ == '__main__':
    print("🚀 Starting Face Recognition Service (Simplified Version)")
    print("📍 Server will run on http://localhost:5000")
    print("🔍 This version uses mock recognition for compatibility")
    print("💡 For production, implement actual face recognition algorithms")
    
    app.run(
        host='0.0.0.0',
        port=5000,
        debug=True,
        threaded=True
    ) 