# Phase 10B API Reference

Quick reference for all Phase 10B endpoints.

---

## Authentication

All endpoints require token authentication via `Authorization` header:
```
Authorization: Bearer {YOUR_API_TOKEN}
```

Token is the `code` field from `users` table (generated on account creation).

---

## Base URL

```
{CLOUD_URL}/external/v1
```

Example: `https://cloud.kontakami.ai/external/v1`

---

## Endpoints

### 1. Ingest API

#### POST /ingest/recording
Receive and process recording from on-prem.

**Request**:
```json
POST /external/v1/ingest/recording
Content-Type: multipart/form-data

{
  "file": <binary>,
  "transcript": "Customer: Hello, I need help with...",
  "filename": "TICKET001_call.wav",
  "duration": 180,
  
  // Ticket data (optional)
  "ticket_id": "TICKET001",
  "customer_name": "John Doe",
  "agent_name": "Agent Smith",
  "intent": "billing",
  "outcome": "resolved",
  "ticket_url": "https://tickets.example.com/001",
  
  // AI quality data from on-prem
  "confidence_score": 0.92,
  "quality_score": 0.85,
  "ai_decision": "upload_approved",
  "pii_detected": false,
  "pii_types": [],
  
  // Encryption metadata
  "encrypted": true,
  "encryption_method": "aes-256-cbc",
  "compressed": false,
  
  // On-prem metadata
  "onprem_metadata": {"agent_id": "A123"},
  "onprem_uploaded_at": "2025-01-30T10:00:00Z"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Recording ingested successfully",
  "data": {
    "recording_id": 123,
    "enrichment_completed": true,
    "decision_generated": true,
    "patterns_detected": 2,
    "sentiment": "joy_positive",
    "recommended_action": "approve"
  }
}
```

---

#### POST /ingest/batch
Batch upload (up to 50 recordings).

**Request**:
```json
{
  "recordings": [
    {
      "transcript": "Transcript 1",
      "filename": "file1.wav",
      "confidence_score": 0.9
    },
    {
      "transcript": "Transcript 2",
      "filename": "file2.wav",
      "confidence_score": 0.8
    }
  ]
}
```

**Response**:
```json
{
  "success": true,
  "message": "Batch ingestion completed: 2 succeeded, 0 failed",
  "data": {
    "total": 2,
    "succeeded": 2,
    "failed": 0,
    "results": [
      {"id": 123, "status": "success"},
      {"id": 124, "status": "success"}
    ]
  }
}
```

---

#### GET /ingest/status/{id}
Check processing status of a recording.

**Response**:
```json
{
  "success": true,
  "data": {
    "recording_id": 123,
    "status": "completed",
    "enrichment_completed": true,
    "decision_generated": true,
    "sentiment": "joy_positive",
    "recommended_action": "approve",
    "created_at": "2025-01-30T10:00:00Z"
  }
}
```

---

### 2. Decision API

#### POST /decision/recommend
Get AI recommendation for a recording.

**Request**:
```json
{
  "recording_id": 123
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "decision_id": 456,
    "recommended_action": "approve",
    "recommendation": "Auto-approve for positive customer experience",
    "can_upsell": true,
    "priority": "low",
    "suggested_action": null,
    "confidence": 0.92,
    "reasoning": "Sentiment: Joy / Satisfaction (polarity: 1.0). Intent: billing. Quality: 0.85",
    "sentiment": "joy_positive",
    "sentiment_polarity": 1.0
  }
}
```

---

#### POST /decision/feedback
Submit feedback on a decision.

**Request**:
```json
{
  "decision_id": 456,
  "feedback": "approved",
  "notes": "Decision was correct",
  "actual_outcome": "Customer satisfied"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Feedback recorded successfully",
  "data": {
    "decision_id": 456,
    "feedback": "approved",
    "was_correct": true
  }
}
```

---

#### GET /decision/history
Get paginated decision history.

**Query Params**:
- `page` (optional, default: 1)

**Response**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 456,
        "recording_id": 123,
        "recommended_action": "approve",
        "priority": "low",
        "created_at": "2025-01-30T10:00:00Z"
      }
    ],
    "total": 50,
    "per_page": 20
  }
}
```

---

#### GET /decision/stats
Get decision statistics.

**Query Params**:
- `date_from` (optional)
- `date_to` (optional)

**Response**:
```json
{
  "success": true,
  "data": {
    "total": 100,
    "by_action": {
      "approve": 60,
      "empathize": 20,
      "escalate": 10,
      "clarify": 10
    },
    "with_feedback": 50,
    "correct_decisions": 45,
    "accuracy_percentage": 90.0
  }
}
```

---

### 3. Enrichment API

#### GET /enrichment/{recording_id}
Get enrichment data for a recording.

**Response**:
```json
{
  "success": true,
  "data": {
    "recording_id": 123,
    "sentiment": {
      "label": "joy_positive",
      "category": "Joy / Satisfaction",
      "polarity": 1.0,
      "emotions": ["happiness", "gratitude"],
      "confidence": 0.95
    },
    "intent": {
      "detected": "billing",
      "confidence": 0.9
    },
    "tone": {
      "detected": "professional",
      "confidence": 0.85
    },
    "ai_action": "reward_upsell",
    "auto_decision": "approve",
    "keywords": ["invoice", "payment", "thank"],
    "risk_score": 0.1,
    "urgency_level": "normal",
    "enriched_at": "2025-01-30T10:00:00Z"
  }
}
```

---

#### POST /enrichment/{recording_id}/retry
Re-run enrichment for a recording.

**Response**:
```json
{
  "success": true,
  "message": "Enrichment completed",
  "data": {
    "sentiment_label": "joy_positive",
    "sentiment_polarity": 1.0,
    "detected_intent": "billing"
  }
}
```

---

#### GET /enrichment/batch
Get bulk enrichment status.

**Response**:
```json
{
  "success": true,
  "data": {
    "total": 100,
    "completed": 95,
    "failed": 2,
    "pending": 3
  }
}
```

---

### 4. Telemetry API

#### GET /telemetry/health
Health check endpoint.

**Response**:
```json
{
  "status": "healthy",
  "timestamp": "2025-01-30T10:00:00Z",
  "services": {
    "database": "up",
    "enrichment": "up",
    "decision_engine": "up"
  }
}
```

---

#### GET /telemetry/status
Detailed system status.

**Response**:
```json
{
  "status": "operational",
  "timestamp": "2025-01-30T10:00:00Z",
  "user_id": 1,
  "statistics": {
    "recordings": {
      "total": 500,
      "today": 20,
      "this_week": 100
    },
    "enrichments": {
      "total": 480,
      "completed": 475,
      "failed": 2,
      "pending": 3
    },
    "decisions": {
      "total": 475,
      "with_feedback": 100,
      "approved": 90,
      "accuracy": 90.0
    },
    "sentiment_distribution": {
      "joy_positive": 200,
      "neutral_neutral": 150,
      "frustration_negative": 50
    }
  }
}
```

---

#### GET /telemetry/metrics
Time-series metrics.

**Query Params**:
- `days` (optional, default: 7)

**Response**:
```json
{
  "success": true,
  "period": {
    "days": 7,
    "start_date": "2025-01-23",
    "end_date": "2025-01-30"
  },
  "metrics": {
    "daily_recordings": [
      {"date": "2025-01-23", "count": 10},
      {"date": "2025-01-24", "count": 15}
    ],
    "sentiment_trends": [
      {"sentiment_label": "joy_positive", "count": 50, "avg_polarity": 0.95}
    ],
    "average_quality_score": 0.85,
    "average_confidence_score": 0.88
  }
}
```

---

#### POST /telemetry/log
Log telemetry data from on-prem (placeholder).

**Request**:
```json
{
  "event": "upload_completed",
  "data": {"count": 10}
}
```

**Response**:
```json
{
  "success": true,
  "message": "Telemetry data received"
}
```

---

### 5. Config API

#### GET /config/user
Get user configuration.

**Response**:
```json
{
  "success": true,
  "data": {
    "ai_confidence_threshold": 0.70,
    "ai_quality_threshold": 0.60,
    "sentiment_threshold": -0.30,
    "auto_approve_enabled": false,
    "pii_detection_enabled": true,
    "pattern_detection_enabled": true,
    "enrichment_enabled": true,
    "custom_rules": null,
    "notification_settings": null
  }
}
```

---

#### PUT /config/user
Update user configuration.

**Request**:
```json
{
  "ai_confidence_threshold": 0.80,
  "auto_approve_enabled": true,
  "custom_rules": [
    {
      "condition": "sentiment_polarity < -0.7",
      "action": "escalate"
    }
  ]
}
```

**Response**:
```json
{
  "success": true,
  "message": "Configuration updated successfully",
  "data": { ...updated config... }
}
```

---

#### POST /config/reset
Reset configuration to defaults.

**Response**:
```json
{
  "success": true,
  "message": "Configuration reset to defaults",
  "data": { ...default config... }
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Recording not found"
}
```

### 422 Validation Error
```json
{
  "success": false,
  "errors": {
    "transcript": ["The transcript field is required."]
  }
}
```

### 500 Server Error
```json
{
  "success": false,
  "message": "Failed to ingest recording",
  "error": "Decryption failed"
}
```

---

## Testing with cURL

### Test Ingest
```bash
curl -X POST http://localhost/external/v1/ingest/recording \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@recording.wav" \
  -F "transcript=This is a test" \
  -F "filename=test.wav"
```

### Test Health
```bash
curl http://localhost/external/v1/telemetry/health \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Test Decision Stats
```bash
curl http://localhost/external/v1/decision/stats \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Rate Limits

No rate limits currently implemented (consider adding in Phase 15).

---

## Versioning

Current version: **v1**  
Prefix: `/external/v1`

Future versions will use `/external/v2`, etc.
