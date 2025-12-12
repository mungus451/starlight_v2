---
layout: default
title: Documentation Development
---

# Documentation Development

This guide explains how to preview the documentation locally.

## Quick Start with Docker (Recommended)

The easiest way to preview docs is using Docker:

```bash
# Start the docs service
docker compose up docs

# View at http://localhost:4000
```

The Jekyll server will watch for changes and automatically rebuild. Press `Ctrl+C` to stop.

## Alternative: Local Jekyll Installation

If you prefer not to use Docker, install Jekyll locally:

### Prerequisites

1. **Ruby** (version 2.7 or higher)
2. **RubyGems**
3. **GCC and Make**

### Installation

```bash
# Install Jekyll and Bundler
gem install jekyll bundler

# Navigate to docs directory
cd docs

# Install dependencies
bundle install

# Serve the site
bundle exec jekyll serve --livereload

# View at http://localhost:4000
```

### macOS Installation

```bash
# Install Ruby with Homebrew
brew install ruby

# Add Ruby to PATH (add to ~/.zshrc)
echo 'export PATH="/opt/homebrew/opt/ruby/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc

# Install Jekyll
gem install jekyll bundler
```

### Ubuntu/Debian Installation

```bash
# Install Ruby and dependencies
sudo apt update
sudo apt install ruby-full build-essential zlib1g-dev

# Configure gem installation directory
echo '# Install Ruby Gems to ~/gems' >> ~/.bashrc
echo 'export GEM_HOME="$HOME/gems"' >> ~/.bashrc
echo 'export PATH="$HOME/gems/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc

# Install Jekyll
gem install jekyll bundler
```

## Documentation Structure

```
docs/
├── _config.yml           # Jekyll configuration
├── index.md              # Main landing page
├── README.md             # Quick navigation
└── agents/               # Agent documentation
    ├── backend-agent.md
    ├── database-architect.md
    ├── docs-agent.md
    ├── frontend-agent.md
    ├── game-balance-architect.md
    ├── review-agent.md
    ├── security-agent.md
    └── testing-agent.md
```

## Writing Documentation

### Markdown Basics

```markdown
# H1 Heading
## H2 Heading
### H3 Heading

**Bold text**
*Italic text*
`code inline`

[Link text](url)

- Bullet point
- Another point

1. Numbered list
2. Second item
```

### Code Blocks

````markdown
```php
// PHP code example
class Example {
    public function method(): void {
        // Code here
    }
}
```

```bash
# Shell commands
docker compose up docs
```
````

### Tables

```markdown
| Column 1 | Column 2 | Column 3 |
|----------|----------|----------|
| Cell 1   | Cell 2   | Cell 3   |
| Cell 4   | Cell 5   | Cell 6   |
```

## Testing Changes

Before committing documentation changes:

1. **Preview locally** to verify formatting
2. **Check all internal links** work
3. **Verify code examples** are correct
4. **Test on mobile** view (resize browser)
5. **Check tables** render properly

## GitHub Pages Deployment

Documentation is automatically deployed via GitHub Actions when:

- Changes are pushed to `master` or `main` branch
- Files in `docs/` directory are modified

The workflow file: `.github/workflows/publish-pages.yml`

### Manual Deployment

You can manually trigger deployment:

1. Go to repository on GitHub
2. Click "Actions" tab
3. Select "Publish GitHub Pages" workflow
4. Click "Run workflow"

## Troubleshooting

### Jekyll Build Errors

```bash
# Clear Jekyll cache
rm -rf docs/.jekyll-cache docs/_site

# Rebuild
docker compose up docs
```

### Port Already in Use

```bash
# Check what's using port 4000
lsof -i :4000

# Kill the process or change port in .env
echo "DOCKER_DOCS_PORT=4001" >> .env
docker compose up docs
```

### Changes Not Appearing

1. Hard refresh browser: `Ctrl+Shift+R` (or `Cmd+Shift+R` on Mac)
2. Clear browser cache
3. Restart Jekyll server

## Live Reload

The Jekyll server includes live reload:

- Make changes to any `.md` file in `docs/`
- Save the file
- Browser automatically refreshes
- See changes immediately

## Tips

- **Keep docs concise** - Developers prefer brief, clear explanations
- **Use real examples** - Take code from actual codebase
- **Include both right and wrong** - Show good and bad patterns
- **Link related docs** - Cross-reference relevant pages
- **Update when code changes** - Keep docs in sync with code
- **Test all commands** - Verify shell commands actually work

## Resources

- [Jekyll Documentation](https://jekyllrb.com/docs/)
- [GitHub Pages Documentation](https://docs.github.com/en/pages)
- [Markdown Guide](https://www.markdownguide.org/)
- [Jekyll Themes](https://pages.github.com/themes/)

## Getting Help

- Documentation issues: Check existing docs for examples
- Jekyll errors: Consult Jekyll documentation
- GitHub Pages: Check GitHub Actions logs in repository
- Code examples: Refer to actual codebase in `app/` directory
