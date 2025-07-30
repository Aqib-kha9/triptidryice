# 🔗 Webhook Integration Guide

## 📋 Overview
This guide explains how to integrate webhooks with Razorpay and Porter for real-time order tracking.

## 🏗️ Webhook URLs

### For Local Development:
```
http://localhost:8000/webhook.php
```

### For Production:
```
https://yourdomain.com/webhook.php
```

## 🔧 Razorpay Webhook Setup

### 1. Login to Razorpay Dashboard
- Go to https://dashboard.razorpay.com
- Navigate to **Settings** → **Webhooks**

### 2. Add Webhook URL
- **Webhook URL**: `https://yourdomain.com/webhook.php`
- **Events to Send**:
  - `payment.captured`
  - `payment.failed`
  - `payment.authorized`

### 3. Webhook Secret
- Copy the webhook secret from Razorpay dashboard
- Add it to your environment variables

## 🚚 Porter Webhook Setup

### 1. Contact Porter Support
- Email Porter support to enable webhooks for your account
- Provide your webhook URL: `https://yourdomain.com/webhook.php`

### 2. Webhook Events
Porter will send updates for:
- Order status changes
- Location updates
- Delivery time estimates

## 📊 Database Integration

### Tables Updated by Webhooks:

1. **orders** table:
   - `payment_status` (Razorpay)
   - `porter_status` (Porter)
   - `updated_at`

2. **porter_tracking** table:
   - `status`
   - `tracking_url`
   - `estimated_delivery_time`
   - `delivery_lat`, `delivery_lng`

3. **system_logs** table:
   - Payment webhook events
   - Porter webhook events

## 🧪 Testing Webhooks

### Test Razorpay Webhook:
```bash
curl -X POST http://localhost:8000/webhook.php \
  -H "Content-Type: application/json" \
  -H "X-Razorpay-Signature: test_signature" \
  -d '{
    "event": "payment.captured",
    "payload": {
      "payment": {
        "entity": {
          "id": "pay_test_123",
          "amount": 123000
        }
      }
    }
  }'
```

### Test Porter Webhook:
```bash
curl -X POST http://localhost:8000/webhook.php \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": "PORTER_123",
    "status": "Out for Delivery",
    "estimated_delivery_time": 1640995200000,
    "location": {
      "lat": 19.0760,
      "lng": 72.8777
    },
    "tracking_url": "https://porter.in/track/123"
  }'
```

## 📝 Logs

All webhook activities are logged to:
- `logs/webhook_debug.txt`

## 🔒 Security

### Razorpay Signature Verification:
```php
// Add this to webhook.php for production
$webhook_secret = 'your_webhook_secret';
$expected_signature = hash_hmac('sha256', $data, $webhook_secret);
$actual_signature = $headers['X-Razorpay-Signature'] ?? '';

if (!hash_equals($expected_signature, $actual_signature)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid signature"]);
    exit;
}
```

## 🚀 Production Checklist

- [ ] Update webhook URLs to production domain
- [ ] Enable Razorpay webhook signature verification
- [ ] Configure Porter webhook with support team
- [ ] Test webhook endpoints
- [ ] Monitor webhook logs
- [ ] Set up error alerts

## 📞 Support

For webhook issues:
1. Check `logs/webhook_debug.txt`
2. Verify webhook URLs are accessible
3. Contact Razorpay/Porter support if needed 