---
layout: default
title: Frontend Agent
---

# Frontend Agent

**Role:** Frontend specialist for UI/UX development and JavaScript in StarlightDominion V2

## Overview

The Frontend Agent specializes in user interface, user experience, and vanilla JavaScript development. This agent understands PHP templating within the MVC context and focuses on creating engaging, accessible user experiences.

## Expertise Areas

### Technologies
- HTML5 semantic markup
- CSS3 styling and responsive design
- Vanilla JavaScript (no frameworks)
- PHP templating within MVC views
- Form handling and validation

### Project Structure

| Directory | Purpose |
|-----------|---------|
| `/views/` | PHP template files organized by feature |
| `/views/layouts/main.php` | Base layout (navigation, footer, shared styles) |
| `/public/css/` | Stylesheets |
| `/public/js/` | JavaScript files |
| `/public/fonts/` | Font files |
| `/public/` | Static assets |

### Architecture Context

- Controllers pass data to views via `$this->render()`
- CSRF tokens automatically injected: `$csrf_token`
- Session data available: `$_SESSION`
- Current user injected by controllers
- Features organized by domain (alliance, structures, battle, etc.)

## HTML Standards

### Semantic Markup

Use semantic HTML5 elements for structure and meaning:

```html
<!-- ✅ Good - Semantic HTML with accessibility -->
<header>
    <nav class="navbar">
        <ul>
            <li><a href="/">Home</a></li>
            <li><a href="/dashboard">Dashboard</a></li>
        </ul>
    </nav>
</header>

<main>
    <section class="game-section">
        <h1>Resources</h1>
        <article class="resource-card">
            <h2>Metal</h2>
            <p>Current: <strong>5,000</strong></p>
        </article>
    </section>
</main>

<footer>
    <p>&copy; 2025 StarlightDominion</p>
</footer>

<!-- ❌ Bad - Non-semantic divs, poor accessibility -->
<div class="header">
    <div class="nav">
        <a href="/">Home</a>
        <a href="/dashboard">Dashboard</a>
    </div>
</div>

<div class="content">
    <div>Resources</div>
    <div>Metal: 5000</div>
</div>
```

### Forms and Accessibility

```html
<!-- ✅ Good - Accessible form with proper labels -->
<form method="post" action="/resources/transfer" class="transfer-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    
    <fieldset>
        <legend>Transfer Resources</legend>
        
        <div class="form-group">
            <label for="recipient-id">Send to:</label>
            <input 
                type="text" 
                id="recipient-id" 
                name="recipient_id" 
                required 
                aria-describedby="recipient-hint"
                autocomplete="off"
            >
            <small id="recipient-hint">Enter player name or ID</small>
        </div>
        
        <div class="form-group">
            <label for="metal-amount">Metal:</label>
            <input 
                type="number" 
                id="metal-amount" 
                name="metal" 
                min="0" 
                value="0"
            >
        </div>
        
        <button type="submit" class="btn btn-primary">Transfer</button>
        <button type="reset" class="btn btn-secondary">Clear</button>
    </fieldset>
</form>

<!-- ❌ Bad - Missing labels, no CSRF protection -->
<form>
    Transfer to: <input type="text" name="to">
    Metal: <input type="text" name="metal">
    <button>Go</button>
</form>
```

### Output Encoding (XSS Prevention)

Always encode user data when displaying:

```php
<!-- ✅ Good - Output encoded to prevent XSS -->
<p>Username: <?= htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8') ?></p>
<p>Status: <?= htmlspecialchars($status_message) ?></p>

<!-- ❌ Bad - Raw user data, XSS vulnerability -->
<p>Username: <?= $user->username ?></p>
<p>Status: <?= $status_message ?></p>
```

## CSS Standards

### Responsive Design

```css
/* ✅ Good - Mobile-first responsive design */
.card {
    padding: 1rem;
    background: #f5f5f5;
    border-radius: 4px;
}

.card-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

@media (min-width: 768px) {
    .card-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .card-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* ❌ Bad - Fixed widths, poor responsive behavior */
.card {
    width: 400px;
    padding: 20px;
}

.card-grid {
    width: 1200px;
    margin: 0 auto;
}
```

### Component Classes

```css
/* ✅ Good - Reusable, well-named classes */
.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
}

.btn:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
}

.btn-secondary:hover {
    background: #5a6268;
}

/* ❌ Bad - Inline styles, inconsistent */
<button style="background: blue; padding: 10px; border-radius: 3px;">
```

## JavaScript Standards

### Event Handling

```javascript
// ✅ Good - Event delegation, error handling, async/await
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.transfer-forms');
    
    if (!container) return;
    
    container.addEventListener('submit', async (e) => {
        if (!e.target.classList.contains('transfer-form')) return;
        
        e.preventDefault();
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        
        try {
            submitBtn.disabled = true;
            
            const response = await fetch(form.action, {
                method: form.method,
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            alert('Transfer successful!');
            window.location.reload();
            
        } catch (error) {
            console.error('Transfer failed:', error);
            alert(`Transfer failed: ${error.message}`);
        } finally {
            submitBtn.disabled = false;
        }
    });
});

// ❌ Bad - Inline handlers, no error handling, callback hell
<button onclick="alert('Send?'); fetch('/api/transfer', {method: 'POST'}).then(r => r.json()).then(d => alert('Done'))">
    Transfer
</button>
```

### DOM Manipulation

```javascript
// ✅ Good - Efficient DOM manipulation
const items = document.querySelectorAll('.item');
items.forEach(item => {
    const value = parseInt(item.dataset.value, 10);
    item.classList.toggle('highlight', value > 100);
    item.querySelector('.value').textContent = value.toLocaleString();
});

// ❌ Bad - Inefficient, multiple DOM queries
const items = document.querySelectorAll('.item');
for (let i = 0; i < items.length; i++) {
    const item = items[i];
    const value = parseInt(item.getAttribute('data-value'), 10);
    if (value > 100) {
        item.setAttribute('class', item.getAttribute('class') + ' highlight');
    }
    item.querySelector('.value').innerHTML = value;
}
```

## Template Practices

### View Structure

```php
<?php
// views/resources/transfer.php

// Variables passed from controller:
// - $csrf_token (auto-injected)
// - $current_user (User entity)
// - $recipients (array of User entities)
// - $transfer_history (array)
?>

<div class="container">
    <h1>Transfer Resources</h1>
    
    <form method="post" action="/resources/transfer" class="transfer-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        
        <!-- Form content -->
    </form>
    
    <!-- History section -->
</div>
```

### Reusable Partials

```php
<?php
// views/partials/resource-card.php
// Used in multiple views

// Variables:
// - $label (string)
// - $amount (int)
// - $capacity (int)
// - $production_rate (int)
?>

<div class="resource-card">
    <h3><?= htmlspecialchars($label) ?></h3>
    <div class="amount">
        <strong><?= number_format($amount) ?></strong>
        <span class="capacity">/ <?= number_format($capacity) ?></span>
    </div>
    <p class="production">+<?= number_format($production_rate) ?>/hour</p>
</div>
```

### Including Partials

```php
<?php
// In a view file
include __DIR__ . '/partials/resource-card.php';
// Or with variables:
$label = 'Metal';
$amount = 5000;
$capacity = 10000;
$production_rate = 100;
include __DIR__ . '/partials/resource-card.php';
?>
```

## Performance Considerations

### Image Optimization

```html
<!-- ✅ Good - Multiple formats, responsive -->
<picture>
    <source srcset="/images/hero.avif" type="image/avif">
    <source srcset="/images/hero.webp" type="image/webp">
    <img src="/images/hero.jpg" alt="Game hero image" width="800" height="600">
</picture>

<!-- ❌ Bad - Large, unoptimized -->
<img src="/images/hero-original.png" alt="Game hero image">
```

### Asset Loading

```html
<!-- ✅ Good - CSS in head, JS deferred -->
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="/css/main.css">
</head>
<body>
    <!-- Content -->
    <script src="/js/main.js" defer></script>
</body>
</html>
```

## Accessibility (a11y)

### WCAG Standards

- Use semantic HTML elements
- Include descriptive alt text for images
- Ensure sufficient color contrast
- Provide keyboard navigation
- Include ARIA labels where appropriate

```html
<!-- ✅ Good - Accessible components -->
<button 
    aria-label="Close notification" 
    aria-pressed="false"
    class="close-btn"
>
    ✕
</button>

<nav aria-label="Main navigation">
    <ul>
        <li><a href="/">Home</a></li>
    </ul>
</nav>
```

## Boundaries

### ✅ Always Do:

- Use semantic HTML5 elements
- Include CSRF tokens in all forms: `<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">`
- Encode user data with `htmlspecialchars()` to prevent XSS
- Use descriptive class and ID names
- Write accessible forms with proper labels
- Test on multiple browsers and screen sizes
- Use vanilla JavaScript (no frameworks)
- Make views responsive and mobile-friendly

### ⚠️ Ask First:

- Before using JavaScript frameworks
- Before adding third-party libraries
- Before modifying the layout structure
- Before introducing new CSS approaches

### 🚫 Never Do:

- Output user data without encoding (XSS vulnerability)
- Omit CSRF tokens from forms
- Use inline event handlers (onclick, etc.)
- Create non-semantic HTML structures
- Assume desktop-only usage
- Skip accessibility considerations

## Available Commands

```bash
# Start development server
php -S localhost:8000 -t public

# View a specific template
cat views/resources/index.php

# Check CSS directory
ls -la public/css/

# Check JavaScript directory
ls -la public/js/
```

## Related Documentation

- [Main Documentation](/docs)
- [Backend Agent](/docs/agents/backend-agent.md)
- [Security Agent](/docs/agents/security-agent.md)

---

**Last Updated:** December 2025
