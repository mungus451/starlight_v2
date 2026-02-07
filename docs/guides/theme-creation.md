# How to Create and Customize a New Theme

This guide explains how to create a new theme and override specific page views, such as the Armory, to test different layouts and features.

## 1. Understanding the Theme System

The theme system is designed to be simple. The application's `BaseController` is configured to look for view files in a specific theme's directory *before* falling back to the default view directory.

- **Default Views:** Located in `views/` (e.g., `views/dashboard/show.php`)
- **Themed Views:** Located in `views/themes/{theme_name}/` (e.g., `views/themes/classic/dashboard/show.php`)

When you render a page, the system first checks if a corresponding file exists in the currently active theme's folder. If it does, that file is used. If not, the default view file is used instead. This means you only need to create files for the pages you want to change.

## 2. Creating a New Theme: Step-by-Step

Let's say you want to create a new theme called `hyperion`.

### Step 1: Create the Theme Directory

Create a new folder for your theme inside `views/themes/`:

```bash
mkdir -p views/themes/hyperion
```

### Step 2: Add the Theme to the Navigation Bar

To make your theme selectable, you need to add it to the dropdown menu in the main layout file.

1.  **Open:** `views/layouts/main.php`
2.  **Find the theme switcher:** Search for the `<select>` element with the `name="theme"`. You will find two of them: one for the desktop view and one for the mobile view.
3.  **Add your theme as a new option** to both dropdowns. Make sure the `value` attribute matches your theme's folder name.

```html
<!-- Add this line inside both <select> elements -->
<option value="hyperion" <?= ($this->session->get('theme', 'default') === 'hyperion') ? 'selected' : '' ?>>Hyperion Theme</option>
```

At this point, you can select the "Hyperion Theme" from the navigation, but since there are no files in your theme's directory, it will still show all the default pages.

## 3. Overriding a Specific View (e.g., The Armory)

Now, let's create a custom version of the Armory page for your `hyperion` theme.

### Step 1: Replicate the Directory Structure

The original Armory view is located at `views/armory/show.php`. To override it, you must create a file with the *exact same path* inside your theme's directory.

```bash
# Create the necessary sub-folder
mkdir -p views/themes/hyperion/armory

mkdir -p public/css/themes/hyperion

# Create the new view file
touch views/themes/hyperion/armory/show.php

touch public/css/themes/hyperion/hyperion.css
```

### Step 2: Copy and Modify the Content

1.  **Copy the entire content** from the original file (`views/armory/show.php`).
2.  **Paste it into your new theme file** (`views/themes/hyperion/armory/show.php`).
3.  **Modify the new file** as much as you want. You can change the HTML structure, add new CSS classes, or remove elements entirely.

### Step 3: Test Your Changes

1.  Run the application and navigate to the Armory page.
2.  Use the theme switcher in the account dropdown to select your "Hyperion Theme".
3.  The page will reload, and you should now see your custom version of the Armory. If you switch back to the "Default Theme", you will see the original version again.

That's it! You can repeat this process for any view file you want to customize for your theme.
