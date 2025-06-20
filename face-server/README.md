# 🤖 Face Recognition Server - Flask AI

Flask-based face recognition service untuk sistem absensi menggunakan library `face_recognition` dan OpenCV.

## 🚀 Fitur Utama

- ✅ **Face Registration** - Registrasi wajah baru dengan encoding vector
- 🎯 **Face Recognition** - Pengenalan wajah dengan confidence score
- 📊 **Real-time Processing** - Pemrosesan gambar real-time
- 🔄 **Auto-reload** - Reload face encodings otomatis
- 📈 **Performance Monitoring** - Monitor CPU, memory, dan disk usage
- 🔐 **CORS Support** - Support untuk frontend Vue.js
- 📝 **Comprehensive Logging** - Log lengkap untuk debugging

## 🗂️ Struktur API

### Endpoints:

- `POST /register-face` - Registrasi wajah baru
- `POST /recognize` - Pengenalan wajah dari foto
- `GET /status/{user_id}` - Status registrasi user
- `DELETE /face/{user_id}` - Hapus data wajah user
- `GET /health` - Health check server
- `GET /stats` - Statistik server

## ⚙️ Installation

### Prerequisites

- Python 3.11+
- pip 23.0+
- OpenCV 4.8+
- CMake (untuk dlib)

### 1. Install Dependencies

#### Linux/Ubuntu:

```bash
# Install system dependencies
sudo apt update
sudo apt install -y python3-dev python3-pip cmake build-essential
sudo apt install -y libopenblas-dev liblapack-dev libx11-dev libgtk-3-dev

# Install Python packages
cd face-server
pip install -r requirements.txt
```

#### Windows:

```bash
# Install Microsoft Visual C++ Build Tools
# Download from: https://visualstudio.microsoft.com/visual-cpp-build-tools/

# Install Python packages
cd face-server
pip install -r requirements.txt
```

#### macOS:

```bash
# Install Homebrew dependencies
brew install cmake

# Install Python packages
cd face-server
pip install -r requirements.txt
```

### 2. Environment Setup

```bash
cp .env.example .env
# Edit .env file sesuai kebutuhan
```

### 3. Create Face Data Directory

```bash
mkdir -p face_data
chmod 755 face_data
```

### 4. Start Development Server

```bash
python app.py
```

### 5. Production Deployment

```bash
# Using Gunicorn
gunicorn -w 4 -b 0.0.0.0:5000 app:app

# Using Docker
docker build -t face-recognition-server .
docker run -p 5000:5000 face-recognition-server
```

## 🔧 Configuration

### Environment Variables

| Variable                 | Description                       | Default       |
| ------------------------ | --------------------------------- | ------------- |
| `FLASK_HOST`             | Server host                       | `0.0.0.0`     |
| `FLASK_PORT`             | Server port                       | `5000`        |
| `FLASK_DEBUG`            | Debug mode                        | `false`       |
| `FACE_DATA_DIR`          | Face data storage directory       | `./face_data` |
| `RECOGNITION_TOLERANCE`  | Face matching tolerance (0.0-1.0) | `0.6`         |
| `FACE_QUALITY_THRESHOLD` | Minimum face quality              | `0.8`         |
| `MAX_FACES_PER_USER`     | Max faces per user                | `5`           |
| `LOG_LEVEL`              | Logging level                     | `INFO`        |

### Face Recognition Settings

**Recognition Tolerance:**

- `0.4` - Very strict (recommended for high security)
- `0.6` - Balanced (recommended for general use)
- `0.8` - Permissive (may have false positives)

**Face Quality Threshold:**

- `0.9` - Very high quality required
- `0.8` - High quality (recommended)
- `0.6` - Medium quality
- `0.4` - Low quality (not recommended)

## 📚 API Documentation

### 1. Register Face

Register wajah baru untuk user tertentu.

**Endpoint:** `POST /register-face`

**Request:**

```json
{
  "user_id": 123,
  "photo": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQ..."
}
```

**Response Success:**

```json
{
  "success": true,
  "message": "Face registered successfully",
  "user_id": 123,
  "registered_at": "2024-01-01T10:00:00"
}
```

**Response Error:**

```json
{
  "success": false,
  "message": "No face detected in image"
}
```

### 2. Recognize Face

Mengenali wajah dari foto yang diberikan.

**Endpoint:** `POST /recognize`

**Request:**

```json
{
  "photo": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQ..."
}
```

**Response Success:**

```json
{
  "success": true,
  "message": "Face recognized successfully",
  "user_id": 123,
  "confidence": 0.8543,
  "recognized_at": "2024-01-01T10:05:00"
}
```

**Response Not Found:**

```json
{
  "success": false,
  "message": "Face not recognized",
  "user_id": "unknown",
  "confidence": 0.0
}
```

### 3. Check Face Status

Cek status registrasi wajah untuk user tertentu.

**Endpoint:** `GET /status/{user_id}`

**Response:**

```json
{
  "success": true,
  "user_id": 123,
  "registered": true,
  "last_updated": "2024-01-01T10:00:00",
  "encoding_count": 1
}
```

### 4. Delete Face Data

Hapus data wajah untuk user tertentu.

**Endpoint:** `DELETE /face/{user_id}`

**Response:**

```json
{
  "success": true,
  "message": "Face data deleted successfully",
  "user_id": 123
}
```

### 5. Health Check

Cek status kesehatan server.

**Endpoint:** `GET /health`

**Response:**

```json
{
  "status": "online",
  "service": "Face Recognition Server",
  "version": "1.0.0",
  "timestamp": "2024-01-01T10:00:00",
  "registered_faces": 5,
  "face_data_dir": "./face_data",
  "recognition_tolerance": 0.6
}
```

### 6. Server Statistics

Monitor performa server.

**Endpoint:** `GET /stats`

**Response:**

```json
{
  "success": true,
  "stats": {
    "registered_users": 5,
    "total_encodings": 5,
    "recognition_tolerance": 0.6,
    "data_directory": "./face_data",
    "server_info": {
      "cpu_percent": 25.4,
      "memory_percent": 45.2,
      "disk_usage": 60.1
    }
  }
}
```

## 🧪 Testing

### Manual Testing

```bash
# Test health endpoint
curl http://localhost:5000/health

# Test face registration
curl -X POST http://localhost:5000/register-face \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "photo": "base64_image_data"}'

# Test face recognition
curl -X POST http://localhost:5000/recognize \
  -H "Content-Type: application/json" \
  -d '{"photo": "base64_image_data"}'
```

### Python Testing Script

```python
import requests
import base64

# Test server health
response = requests.get('http://localhost:5000/health')
print(response.json())

# Test face registration
with open('test_image.jpg', 'rb') as img_file:
    img_base64 = base64.b64encode(img_file.read()).decode()

response = requests.post('http://localhost:5000/register-face', json={
    'user_id': 1,
    'photo': f'data:image/jpeg;base64,{img_base64}'
})
print(response.json())
```

## 🐳 Docker Support

### Dockerfile

```dockerfile
FROM python:3.11-slim

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    cmake \
    build-essential \
    libopenblas-dev \
    liblapack-dev \
    libx11-dev \
    && rm -rf /var/lib/apt/lists/*

# Install Python dependencies
COPY requirements.txt .
RUN pip install -r requirements.txt

# Copy application code
COPY . .

# Create face data directory
RUN mkdir -p face_data

EXPOSE 5000

CMD ["gunicorn", "-w", "4", "-b", "0.0.0.0:5000", "app:app"]
```

### Build & Run

```bash
# Build image
docker build -t face-recognition-server .

# Run container
docker run -d \
  --name face-server \
  -p 5000:5000 \
  -v $(pwd)/face_data:/app/face_data \
  face-recognition-server

# Check logs
docker logs face-server
```

## 📊 Performance Optimization

### Hardware Requirements

**Minimum:**

- CPU: 2 cores, 2.0 GHz
- RAM: 2 GB
- Storage: 1 GB

**Recommended:**

- CPU: 4 cores, 2.5 GHz
- RAM: 4 GB
- Storage: 5 GB
- GPU: Optional (CUDA support)

### Optimization Tips

1. **Face Image Quality:**

   - Resolution: 640x480 minimum
   - Format: JPEG dengan quality 85%
   - Lighting: Good lighting conditions
   - Face size: At least 100x100 pixels

2. **Server Performance:**

   - Use multiple workers: `gunicorn -w 4`
   - Enable compression: `gzip`
   - Cache face encodings in memory
   - Use SSD storage for face_data

3. **Recognition Accuracy:**
   - Register multiple angles per user
   - Update encodings periodically
   - Filter low-quality images
   - Adjust tolerance based on use case

## 🐛 Troubleshooting

### Common Issues

**1. dlib Installation Failed**

```bash
# Install build tools first
sudo apt install build-essential cmake

# On Windows, install Visual Studio Build Tools
# Then reinstall dlib
pip uninstall dlib
pip install dlib
```

**2. Face Detection Not Working**

```bash
# Check image format and quality
# Ensure good lighting
# Verify only one face in image
# Check image resolution (minimum 300x300)
```

**3. Low Recognition Accuracy**

```bash
# Increase recognition tolerance
# Register multiple face angles
# Ensure consistent lighting conditions
# Clean and update face encodings
```

**4. High Memory Usage**

```bash
# Reduce MAX_FACES_PER_USER
# Implement face encoding compression
# Use Redis for external caching
# Monitor with /stats endpoint
```

**5. Server Performance Issues**

```bash
# Increase worker processes
# Monitor CPU/memory with /stats
# Optimize image processing pipeline
# Use GPU acceleration if available
```

## 🔒 Security Considerations

### Production Deployment

- [ ] Use HTTPS/TLS encryption
- [ ] Implement API authentication
- [ ] Rate limiting per IP
- [ ] Input validation and sanitization
- [ ] Secure face data storage
- [ ] Regular security updates
- [ ] Monitor for suspicious activity

### Data Privacy

- Face encodings are mathematical representations, not images
- Original photos are not stored on server
- Implement GDPR compliance for EU users
- Provide user data deletion capabilities

## 📈 Monitoring & Logging

### Log Files

- `face_server.log` - Application logs
- Access logs via web server (nginx/apache)
- Error tracking with Sentry (optional)

### Metrics to Monitor

- Recognition accuracy rate
- Response time per request
- Memory usage trends
- Face registration success rate
- Error rate and types

### Health Checks

```bash
# Basic health check
curl http://localhost:5000/health

# Detailed stats
curl http://localhost:5000/stats

# Check face data integrity
ls -la face_data/
```

## 📞 Support

- **Documentation**: [Wiki](docs/)
- **Issue Tracking**: [GitHub Issues](issues/)
- **Performance Tuning**: [Optimization Guide](docs/optimization.md)

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

---

**Dibuat dengan ❤️ menggunakan Python 3.11 & face_recognition library**
