# Deletion Form Shortcode Usage

## Overview

The `[monigpdr_deletion_form]` shortcode allows you to embed the account deletion form anywhere on your WordPress site. This provides maximum flexibility for placing the deletion functionality where your users need it most.

## Basic Usage

### Simple Form
```
[monigpdr_deletion_form]
```

### Custom Title and Subtitle
```
[monigpdr_deletion_form title="Remove My Data" subtitle="Request permanent deletion of your account and personal data"]
```

### Minimal Style (No Header)
```
[monigpdr_deletion_form show_title="false" show_subtitle="false" style="minimal"]
```

### Card Style
```
[monigpdr_deletion_form style="card" title="Account Deletion" subtitle="Permanently delete your account"]
```

## Shortcode Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `title` | string | "Delete Account" | Custom title for the form |
| `subtitle` | string | "Request deletion of your account..." | Custom subtitle/description |
| `show_title` | boolean | "true" | Show/hide the title ("true" or "false") |
| `show_subtitle` | boolean | "true" | Show/hide the subtitle ("true" or "false") |
| `style` | string | "default" | Form styling ("default", "minimal", "card") |
| `class` | string | "monigpdr-deletion-form-embedded" | Additional CSS class |

## Style Options

### 1. Default Style
- Full styling with background and shadows
- Shows title and subtitle
- Best for dedicated pages or sections

```
[monigpdr_deletion_form style="default"]
```

### 2. Minimal Style
- Clean, minimal appearance
- No background or borders
- Hides header by default
- Perfect for embedding in existing content

```
[monigpdr_deletion_form style="minimal" show_title="false" show_subtitle="false"]
```

### 3. Card Style
- Card-like appearance with subtle shadow
- Compact design
- Good for sidebars or content areas

```
[monigpdr_deletion_form style="card" title="Delete Account"]
```

## Usage Examples

### 1. Privacy Policy Page
Add to your privacy policy page to make account deletion easily accessible:

```
<h2>Your Rights</h2>
<p>Under GDPR, you have the right to request deletion of your personal data.</p>

[monigpdr_deletion_form 
    title="Request Data Deletion" 
    subtitle="Use this form to permanently delete your account and all associated data"
    style="card"
]
```

### 2. Account Settings Page
Embed in user account settings for easy access:

```
<h3>Account Management</h3>
<p>Manage your account settings and data.</p>

[monigpdr_deletion_form 
    title="Delete My Account" 
    subtitle="⚠️ This action cannot be undone"
    style="minimal"
    show_title="true"
    show_subtitle="true"
]
```

### 3. Footer or Sidebar
Add to footer or sidebar for global access:

```
[monigpdr_deletion_form 
    title="Delete Account" 
    style="minimal"
    show_title="false"
    show_subtitle="false"
]
```

### 4. Mobile App Integration
For mobile app deep linking, you can create a dedicated page:

```
[monigpdr_deletion_form 
    title="Account Deletion" 
    subtitle="Request deletion of your account. You will receive a confirmation email."
    style="default"
]
```

## Language Support

The shortcode automatically detects the current language and displays text accordingly:

- **English**: Default language
- **German**: When WPML/Polylang is set to German
- **Custom**: Uses your admin settings for email templates

## Security Features

The embedded form includes all security features:

- ✅ CSRF protection with nonces
- ✅ Email confirmation required
- ✅ 6-digit verification codes
- ✅ Rate limiting protection
- ✅ Input validation and sanitization

## Styling Customization

### Custom CSS Classes
Add your own styling by using the `class` parameter:

```
[monigpdr_deletion_form class="my-custom-deletion-form"]
```

Then add CSS:
```css
.my-custom-deletion-form {
    border: 2px solid #e74c3c;
    border-radius: 10px;
    padding: 20px;
}
```

### Responsive Design
The form is fully responsive and works on all devices:

- Mobile phones
- Tablets
- Desktop computers
- High-resolution displays

## Integration Examples

### 1. WooCommerce Integration
Add to WooCommerce account page:

```php
// In your theme's woocommerce/myaccount/dashboard.php
echo do_shortcode('[monigpdr_deletion_form style="card" title="Delete My Account"]');
```

### 2. BuddyPress Integration
Add to BuddyPress profile:

```php
// In your theme's bp-default/members/single/profile.php
echo do_shortcode('[monigpdr_deletion_form style="minimal"]');
```

### 3. Custom Page Template
Create a dedicated deletion page:

```php
<?php
/*
Template Name: Account Deletion
*/

get_header(); ?>

<div class="deletion-page">
    <h1>Account Deletion</h1>
    <p>Use the form below to request deletion of your account.</p>
    
    <?php echo do_shortcode('[monigpdr_deletion_form style="default"]'); ?>
</div>

<?php get_footer(); ?>
```

## Troubleshooting

### Form Not Appearing
1. Check if public deletion is enabled in plugin settings
2. Verify the shortcode syntax
3. Clear any caching plugins

### Styling Issues
1. Check for CSS conflicts with your theme
2. Use browser developer tools to inspect elements
3. Add custom CSS to override conflicting styles

### JavaScript Errors
1. Ensure jQuery is loaded
2. Check browser console for errors
3. Verify no JavaScript conflicts

## Best Practices

### 1. Placement
- Place in easily accessible locations
- Consider user privacy and data protection
- Make it discoverable but not intrusive

### 2. Messaging
- Use clear, understandable language
- Explain the consequences of deletion
- Provide alternative options if possible

### 3. User Experience
- Keep forms simple and intuitive
- Provide clear feedback at each step
- Ensure mobile-friendly design

### 4. Compliance
- Follow GDPR requirements
- Document the deletion process
- Maintain audit trails

## Advanced Usage

### Conditional Display
Show the form only to logged-in users:

```php
<?php if (is_user_logged_in()): ?>
    <?php echo do_shortcode('[monigpdr_deletion_form]'); ?>
<?php else: ?>
    <p>Please log in to access account deletion.</p>
<?php endif; ?>
```

### Custom Styling
Create a completely custom appearance:

```css
/* Custom deletion form styling */
.monigpdr-deletion-form-embedded {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
}

.monigpdr-deletion-form-embedded .monigpdr-input {
    background: rgba(255, 255, 255, 0.9);
    border: none;
    color: #333;
}

.monigpdr-deletion-form-embedded .monigpdr-btn {
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid white;
    color: white;
}
```

## Support

For support and questions:
- Check the main plugin documentation
- Review the code comments
- Test in a staging environment first
- Contact plugin support if needed 