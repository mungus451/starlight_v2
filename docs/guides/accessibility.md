# Accessibility Guide

StarlightDominion V2 is built with accessibility as a core consideration. All features should be usable by players using assistive technologies.

## Semantic HTML

### Use Appropriate Elements

```php
<!-- From views/bank/show.php -->
<form action="/bank/deposit" method="POST">
    <label for="dep-amount-display">Amount to Deposit (Max 80%)</label>
    <input type="text" 
           id="dep-amount-display" 
           class="formatted-amount" 
           placeholder="e.g., 1,000,000" 
           required>
    
    <button type="submit" class="btn-submit">Deposit</button>
</form>
```

### Proper Heading Hierarchy

```php
<h1>Bank</h1>
    <h2>Deposit</h2>
    <h2>Withdraw</h2>
    <h2>Transfer</h2>
```

## Form Accessibility

### Labels for All Inputs

Every form input must have an associated label:

```php
<label for="amount">Amount</label>
<input type="number" id="amount" name="amount" required>
```

### Required Field Indication

```php
<label for="password">
    Password <span aria-label="required">*</span>
</label>
<input type="password" 
       id="password" 
       name="password" 
       required 
       aria-required="true">
```

### Error Messages

```php
<?php if ($error = $this->session->getFlash('error')): ?>
    <div class="alert alert-error" role="alert" aria-live="polite">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>
```

## Keyboard Navigation

### Tab Order

All interactive elements are keyboard accessible in logical order:

```php
<!-- Natural tab order: email → password → submit -->
<form action="/login" method="POST">
    <input type="email" name="email" tabindex="1">
    <input type="password" name="password" tabindex="2">
    <button type="submit" tabindex="3">Login</button>
</form>
```

### Focus States

CSS provides clear focus indicators:

```css
button:focus,
input:focus,
select:focus {
    outline: 2px solid #4CAF50;
    outline-offset: 2px;
}
```

### Skip Links

Main navigation includes skip-to-content link:

```php
<a href="#main-content" class="skip-link">
    Skip to main content
</a>

<main id="main-content">
    <!-- Page content -->
</main>
```

## ARIA Attributes

### Live Regions

Dynamic content uses appropriate ARIA roles:

```php
<div class="timer-countdown" 
     role="timer" 
     aria-live="polite"
     data-last-deposit="<?= htmlspecialchars($stats->last_deposit_at ?? '') ?>">
    Next charge in: <span id="countdown">--:--</span>
</div>
```

### Button States

```php
<button type="submit" 
        class="btn-submit" 
        <?= $stats->deposit_charges <= 0 ? 'disabled aria-disabled="true"' : '' ?>>
    <?= $stats->deposit_charges <= 0 ? 'No Charges Left' : 'Deposit' ?>
</button>
```

### Loading States

```php
<button type="submit" 
        aria-busy="<?= $isLoading ? 'true' : 'false' ?>"
        aria-label="<?= $isLoading ? 'Processing...' : 'Submit' ?>">
    Submit
</button>
```

## Color Contrast

### WCAG AA Compliance

All text meets WCAG 2.1 Level AA contrast ratios:

- **Normal text**: 4.5:1 minimum
- **Large text** (18pt+): 3:1 minimum
- **UI components**: 3:1 minimum

### Don't Rely on Color Alone

```php
<!-- ✅ Good: Uses icon + color + text -->
<span class="status-<?= $status ?>">
    <i class="icon-<?= $status ?>" aria-hidden="true"></i>
    <?= ucfirst($status) ?>
</span>

<!-- ❌ Bad: Color only -->
<span style="color: green;">Success</span>
```

## Screen Reader Support

### Image Alt Text

```php
<img src="/images/logo.png" 
     alt="StarlightDominion V2 logo">
```

### Decorative Images

```php
<img src="/images/divider.png" 
     alt="" 
     role="presentation">
```

### Icon Buttons

```php
<button type="button" aria-label="Close dialog">
    <i class="icon-close" aria-hidden="true"></i>
</button>
```

## Testing

### Manual Testing

1. **Keyboard Only**: Navigate entire site using only Tab, Enter, Escape
2. **Screen Reader**: Test with NVDA (Windows) or VoiceOver (Mac)
3. **Zoom**: Test at 200% zoom level

### Automated Tools

```bash
# Install Lighthouse
npm install -g lighthouse

# Run accessibility audit
lighthouse http://localhost:8000 --only-categories=accessibility
```

### Browser Extensions

- **axe DevTools** - Automated accessibility testing
- **WAVE** - Visual feedback about accessibility
- **Accessibility Insights** - Comprehensive assessment

## Common Patterns

### Data Tables

```php
<table>
    <thead>
        <tr>
            <th scope="col">Empire Name</th>
            <th scope="col">Power</th>
            <th scope="col">Rank</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <th scope="row"><?= htmlspecialchars($user->empire_name) ?></th>
            <td><?= number_format($user->power) ?></td>
            <td><?= $user->rank ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

### Modal Dialogs

```php
<div class="modal" 
     role="dialog" 
     aria-labelledby="modal-title" 
     aria-modal="true">
    <h2 id="modal-title">Confirm Action</h2>
    <p>Are you sure you want to proceed?</p>
    <button type="button" aria-label="Confirm">Yes</button>
    <button type="button" aria-label="Cancel">No</button>
</div>
```

## Accessibility Checklist

- [ ] All images have alt text or role="presentation"
- [ ] All form inputs have associated labels
- [ ] All interactive elements are keyboard accessible
- [ ] Color contrast meets WCAG AA standards
- [ ] Error messages are associated with form fields
- [ ] Dynamic content uses aria-live regions
- [ ] Focus indicators are clearly visible
- [ ] Skip links are provided for main content
- [ ] Headings follow proper hierarchy
- [ ] Tables use proper scope attributes

## Resources

- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [Accessibility Insights](https://accessibilityinsights.io/)
- [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)
