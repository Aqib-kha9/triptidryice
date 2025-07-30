# Tripti Dry Ice - Checkout System

## Overview
This is a comprehensive 4-step checkout system for Tripti Dry Ice that handles PIN code validation, location detection, order summary, and payment processing with Porter integration.

## Features

### 1. PIN Code Validation
- Validates 6-digit PIN codes for Mumbai area delivery
- Includes comprehensive list of valid PIN codes
- Real-time validation with error messages
- Stores validated PIN code in localStorage

### 2. Location Detection & Customer Details
- **Automatic Location Detection**: Uses browser geolocation API
- **Interactive Map**: Shows user location using Leaflet maps
- **Reverse Geocoding**: Pre-fills address using OpenCage API
- **Manual Location**: Fallback button for location access issues
- **Customer Form**: Collects all necessary delivery information

### 3. Order Summary
- Displays cart items with quantities and prices
- Calculates subtotal, GST (18%), and shipping costs
- Shows final total amount
- Integrates with shipping estimation API

### 4. Payment Processing
- **Razorpay Integration**: Secure payment gateway
- **Development Mode**: Fake payment for testing
- **Porter Integration**: Sends order data to Porter for delivery
- **Success Handling**: Redirects to success page after payment

## File Structure

```
├── checkout.html          # Main checkout page
├── js/
│   └── checkout.js       # Checkout functionality
├── get_shipping_estimate.php  # Shipping cost calculation
├── save_order.php        # Order saving and Porter integration
└── README_CHECKOUT.md    # This file
```

## Setup Instructions

### 1. API Keys Required
- **OpenCage API Key**: For reverse geocoding
  - Get from: https://opencagedata.com/
  - Replace `YOUR_OPENCAGE_API_KEY` in `js/checkout.js`

- **Razorpay Key**: For payment processing
  - Get from: https://razorpay.com/
  - Replace `rzp_live_zEkVtK7a6pfmAT` in `js/checkout.js`

### 2. PHP Backend Files
- `get_shipping_estimate.php`: Calculate shipping costs
- `save_order.php`: Save orders and integrate with Porter

### 3. Cart Integration
The system expects cart data in localStorage with key `triptiCart`:
```javascript
[
  {
    name: "Product Name",
    price: 100.00,
    qty: 2
  }
]
```

## How It Works

### Step 1: PIN Code Validation
1. User enters 6-digit PIN code
2. System validates against Mumbai area PIN codes
3. If valid, proceeds to Step 2
4. If invalid, shows error message

### Step 2: Location & Customer Details
1. **Auto-location Detection**:
   - Requests browser location permission
   - Gets coordinates using geolocation API
   - Shows location on interactive map
   - Pre-fills address using reverse geocoding

2. **Manual Location** (if auto-detection fails):
   - Shows "Use My Location" button
   - User can manually trigger location detection

3. **Customer Form**:
   - Collects name, phone, email, address
   - Optional fields: landmark, apartment
   - Validates all required fields

### Step 3: Order Summary
1. **Shipping Estimation**:
   - Sends customer data to `get_shipping_estimate.php`
   - Calculates shipping cost based on location

2. **Price Calculation**:
   - Subtotal: Sum of all cart items
   - GST: 18% of subtotal
   - Shipping: From estimation API
   - Total: Subtotal + GST + Shipping

3. **Display**:
   - Shows cart items in table
   - Displays price breakdown
   - Shows final total

### Step 4: Payment
1. **Razorpay Integration**:
   - Creates payment options
   - Handles payment processing
   - Development mode uses fake payment ID

2. **Order Saving**:
   - Sends complete order data to `save_order.php`
   - Integrates with Porter for delivery booking
   - Stores order data in localStorage

3. **Success Handling**:
   - Shows success message
   - Redirects to home page

## Technical Details

### Dependencies
- **Bootstrap 5.3.0**: UI framework
- **Font Awesome 6.0.0**: Icons
- **Leaflet 1.9.4**: Interactive maps
- **Razorpay**: Payment gateway

### Browser Requirements
- Modern browser with geolocation support
- HTTPS required for geolocation (in production)
- JavaScript enabled

### LocalStorage Keys
- `triptiCart`: Cart items
- `deliveryPinCode`: Validated PIN code
- `customerDetails`: Customer information
- `shippingCost`: Calculated shipping cost
- `porterOrderData`: Order data after Porter integration

## Error Handling

### Location Detection Errors
- **Permission Denied**: Shows manual location button
- **Timeout**: Retry mechanism
- **Unavailable**: Clear error message
- **Browser Support**: Graceful degradation

### Payment Errors
- **Cart Empty**: Prevents payment initiation
- **API Failures**: Shows user-friendly messages
- **Network Issues**: Retry options

### Form Validation
- **Required Fields**: Real-time validation
- **PIN Code**: 6-digit format validation
- **Phone**: 10-digit format validation
- **Email**: Email format validation

## Customization

### Styling
- CSS classes for easy customization
- Bootstrap-based responsive design
- Custom color scheme support

### PIN Codes
- Edit `validPinCodes` array in `js/checkout.js`
- Add/remove PIN codes as needed

### Shipping Logic
- Modify `get_shipping_estimate.php` for custom shipping rules
- Integrate with different shipping providers

### Payment Gateway
- Replace Razorpay with other gateways
- Modify payment options in `initiatePayment()` function

## Testing

### Development Mode
- Set `devMode = true` in `js/checkout.js`
- Uses fake payment ID for testing
- Bypasses actual Razorpay integration

### Production Mode
- Set `devMode = false`
- Uses real Razorpay integration
- Requires valid API keys

## Security Considerations

1. **HTTPS Required**: For geolocation and payment processing
2. **API Key Protection**: Store keys securely
3. **Input Validation**: Server-side validation required
4. **CSRF Protection**: Implement CSRF tokens
5. **Data Encryption**: Encrypt sensitive data

## Troubleshooting

### Common Issues

1. **Location Not Working**:
   - Check HTTPS requirement
   - Verify browser permissions
   - Test with manual location button

2. **Payment Fails**:
   - Verify Razorpay API key
   - Check network connectivity
   - Review browser console for errors

3. **Shipping Cost Issues**:
   - Check `get_shipping_estimate.php` configuration
   - Verify API endpoints
   - Review server logs

### Debug Mode
- Open browser console for detailed logs
- Check network tab for API calls
- Verify localStorage data

## Support

For technical support or customization requests, please contact the development team.

---

**Note**: This system is designed for Tripti Dry Ice delivery service in Mumbai area. Modify PIN codes and shipping logic for other locations. 