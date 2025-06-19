#!/bin/bash

# Git Integration Setup Script
# Automatisiert Git-Setup für das WordPress Plugin

set -e

PLUGIN_DIR="/Applications/XAMPP/xamppfiles/htdocs/wordpress/wp-content/plugins/markuslehr_clientgallerie"
cd "$PLUGIN_DIR"

echo "🚀 Setting up Git integration for MarkusLehr ClientGallerie Plugin..."

# Check if Git is already initialized
if [ ! -d ".git" ]; then
    echo "📁 Initializing Git repository..."
    git init
    
    # Create .gitignore if it doesn't exist
    if [ ! -f ".gitignore" ]; then
        cat > .gitignore << 'EOF'
# WordPress
wp-config.php
wp-content/uploads/
wp-content/cache/
wp-content/backup-db/
wp-content/updraft/
wp-content/backupwordpress-*/

# Plugin specific
vendor/
node_modules/
logs/*.log
*.log

# Development
.DS_Store
.vscode/settings.json
.idea/
*.swp
*.swo

# Temporary files
*.tmp
*.temp
.cache/

# Environment files
.env
.env.local
.env.production

# Build artifacts
dist/
build/
.next/
EOF
        echo "✅ Created .gitignore"
    fi
else
    echo "✅ Git repository already exists"
fi

# Configure Git user if not set
if [ -z "$(git config user.name)" ]; then
    git config user.name "Markus Lehr AI Assistant"
    echo "✅ Set Git user name"
fi

if [ -z "$(git config user.email)" ]; then
    git config user.email "ai@markuslehr.com"
    echo "✅ Set Git user email"
fi

# Create develop branch if it doesn't exist
if ! git show-ref --verify --quiet refs/heads/develop; then
    git checkout -b develop
    echo "✅ Created develop branch"
fi

# Install Git hooks
echo "🔧 Installing Git hooks..."

# Pre-commit hook
cat > .git/hooks/pre-commit << 'EOF'
#!/bin/bash

echo "🔍 Running pre-commit checks..."

# Run Composer autoloader check
if [ -f "composer.json" ]; then
    composer dump-autoload --optimize --quiet
    echo "✅ Autoloader regenerated"
fi

# Run PHP syntax check on staged files
for file in $(git diff --cached --name-only --diff-filter=ACM | grep '\.php$'); do
    if [ -f "$file" ]; then
        php -l "$file"
        if [ $? -ne 0 ]; then
            echo "❌ PHP syntax error in $file"
            exit 1
        fi
    fi
done

# Run AI self-diagnosis
if [ -f "scripts/ai-self-diagnosis.php" ]; then
    php scripts/ai-self-diagnosis.php --quick
fi

echo "✅ Pre-commit checks passed"
EOF

chmod +x .git/hooks/pre-commit

# Post-commit hook
cat > .git/hooks/post-commit << 'EOF'
#!/bin/bash

echo "📝 Post-commit actions..."

# Update version in main plugin file if this is a release commit
COMMIT_MSG=$(git log -1 --pretty=%B)
if [[ $COMMIT_MSG == release:* ]]; then
    echo "🏷️ Release commit detected, consider updating version numbers"
fi

# Generate documentation
if [ -f "scripts/generate-docs.php" ]; then
    php scripts/generate-docs.php --quiet
fi

echo "✅ Post-commit actions completed"
EOF

chmod +x .git/hooks/post-commit

# Create commit message template
cat > .gitmessage << 'EOF'
# <type>: <subject>
#
# <body>
#
# <footer>

# Type should be one of the following:
# * feat: A new feature
# * fix: A bug fix
# * docs: Documentation only changes
# * style: Changes that do not affect the meaning of the code
# * refactor: A code change that neither fixes a bug nor adds a feature
# * perf: A code change that improves performance
# * test: Adding missing tests or correcting existing tests
# * build: Changes that affect the build system or external dependencies
# * ci: Changes to our CI configuration files and scripts
# * chore: Other changes that don't modify src or test files
# * revert: Reverts a previous commit

# Subject line should:
# * Be 50 characters or less
# * Start with a capital letter
# * Not end with a period
# * Use imperative mood

# Body should:
# * Wrap at 72 characters
# * Explain what and why vs. how
# * Can include multiple paragraphs

# Footer should:
# * Include breaking changes (BREAKING CHANGE: ...)
# * Include issue references (Closes #123, Fixes #456)
EOF

git config commit.template .gitmessage

# Create release branches structure
echo "🌿 Setting up branch structure..."

# Development workflow branches
BRANCHES=("feature/database-optimization" "feature/admin-ui" "hotfix/security-patches")

for branch in "${BRANCHES[@]}"; do
    if ! git show-ref --verify --quiet "refs/heads/$branch"; then
        git checkout -b "$branch" develop
        git checkout develop
        echo "✅ Created branch: $branch"
    fi
done

# Set up remote repository (placeholder)
echo "🌐 Setting up remote repository..."

# Check if we should make this a private repository
read -p "Do you want to set up a private remote repository? (y/N): " setup_remote

if [[ $setup_remote =~ ^[Yy]$ ]]; then
    echo "📡 To set up private repository:"
    echo "1. Create a private repository on GitHub/GitLab"
    echo "2. Run: git remote add origin <repository-url>"
    echo "3. Run: git push -u origin develop"
    echo ""
    echo "Example commands:"
    echo "git remote add origin git@github.com:markuslehr/clientgallerie-private.git"
    echo "git push -u origin develop"
fi

# Create AI assistant helper script
cat > scripts/git-ai-helper.sh << 'EOF'
#!/bin/bash

# AI Assistant Git Helper
# Provides intelligent Git operations for the AI system

case "$1" in
    "smart-commit")
        # Analyze changes and create intelligent commit message
        CHANGED_FILES=$(git diff --cached --name-only)
        
        if [ -z "$CHANGED_FILES" ]; then
            echo "No staged changes found"
            exit 1
        fi
        
        # Generate commit message based on changes
        if echo "$CHANGED_FILES" | grep -q "src/.*Repository"; then
            TYPE="feat"
            SCOPE="repository"
        elif echo "$CHANGED_FILES" | grep -q "src/.*Schema"; then
            TYPE="feat"
            SCOPE="database"
        elif echo "$CHANGED_FILES" | grep -q "src/.*Controller"; then
            TYPE="feat"
            SCOPE="admin"
        elif echo "$CHANGED_FILES" | grep -q "scripts/"; then
            TYPE="build"
            SCOPE="scripts"
        else
            TYPE="feat"
            SCOPE="core"
        fi
        
        MESSAGE="${TYPE}(${SCOPE}): ${2:-Update implementation}"
        
        git commit -m "$MESSAGE"
        echo "✅ Committed with message: $MESSAGE"
        ;;
        
    "safe-merge")
        # Safe merge with backup
        CURRENT_BRANCH=$(git branch --show-current)
        TARGET_BRANCH="$2"
        
        if [ -z "$TARGET_BRANCH" ]; then
            echo "Usage: $0 safe-merge <target-branch>"
            exit 1
        fi
        
        # Create backup branch
        BACKUP_BRANCH="${CURRENT_BRANCH}-backup-$(date +%Y%m%d-%H%M%S)"
        git checkout -b "$BACKUP_BRANCH"
        git checkout "$CURRENT_BRANCH"
        
        # Perform merge
        git merge "$TARGET_BRANCH"
        
        if [ $? -eq 0 ]; then
            echo "✅ Merge successful. Backup created: $BACKUP_BRANCH"
        else
            echo "❌ Merge failed. Backup available: $BACKUP_BRANCH"
            exit 1
        fi
        ;;
        
    "health-check")
        # Run comprehensive health check
        echo "🔍 Running Git health check..."
        
        # Check for uncommitted changes
        if [ -n "$(git status --porcelain)" ]; then
            echo "⚠️ Uncommitted changes found:"
            git status --porcelain
        else
            echo "✅ Working directory clean"
        fi
        
        # Check for unpushed commits
        UNPUSHED=$(git log @{u}.. --oneline 2>/dev/null)
        if [ -n "$UNPUSHED" ]; then
            echo "📤 Unpushed commits:"
            echo "$UNPUSHED"
        else
            echo "✅ All commits pushed"
        fi
        
        # Check branch status
        CURRENT_BRANCH=$(git branch --show-current)
        echo "🌿 Current branch: $CURRENT_BRANCH"
        
        # Run self-diagnosis
        if [ -f "scripts/ai-self-diagnosis.php" ]; then
            php scripts/ai-self-diagnosis.php --git-focus
        fi
        ;;
        
    *)
        echo "AI Assistant Git Helper"
        echo "Usage: $0 {smart-commit|safe-merge|health-check}"
        echo ""
        echo "Commands:"
        echo "  smart-commit [message]  - Intelligent commit with auto-generated message"
        echo "  safe-merge <branch>     - Safe merge with automatic backup"
        echo "  health-check           - Comprehensive repository health check"
        ;;
esac
EOF

chmod +x scripts/git-ai-helper.sh

# Initial commit if needed
if [ -z "$(git log --oneline 2>/dev/null)" ]; then
    echo "📝 Creating initial commit..."
    git add .
    git commit -m "chore: Initial plugin setup with modern architecture

- Added PSR-4 autoloading
- Implemented Repository pattern
- Added Schema management system
- Created Migration system
- Set up comprehensive logging
- Added AI self-diagnosis tools
- Configured Git workflow"
    
    echo "✅ Initial commit created"
fi

# Create README for Git workflow
cat > GIT-WORKFLOW.md << 'EOF'
# Git Workflow für MarkusLehr ClientGallerie Plugin

## 🌿 Branch Strategy

```
main (production)
├── develop (integration)
│   ├── feature/database-optimization
│   ├── feature/admin-ui
│   └── feature/new-feature
├── hotfix/security-patches
└── release/v1.1.0
```

## 🚀 Development Workflow

### 1. Feature Development
```bash
# Create feature branch
git checkout develop
git pull origin develop
git checkout -b feature/your-feature-name

# Development cycle
git add .
git commit -m "feat(scope): your change description"

# Push feature
git push -u origin feature/your-feature-name
```

### 2. AI Assistant Integration
```bash
# Use AI helper for intelligent commits
./scripts/git-ai-helper.sh smart-commit "your change description"

# Run health checks
./scripts/git-ai-helper.sh health-check

# Safe merging
./scripts/git-ai-helper.sh safe-merge develop
```

### 3. Code Quality Gates
```bash
# Before commit (automatic via pre-commit hook)
composer dump-autoload --optimize
php -l src/**/*.php
php scripts/ai-self-diagnosis.php

# Before merge
composer run quality:check
composer run test
```

## 📋 Commit Message Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `style`: Code style changes
- `refactor`: Code refactoring
- `perf`: Performance improvements
- `test`: Tests
- `build`: Build system
- `ci`: CI configuration
- `chore`: Maintenance

### Scopes:
- `repository`: Repository pattern changes
- `schema`: Database schema changes
- `migration`: Migration system
- `admin`: Admin interface
- `api`: REST API
- `security`: Security improvements
- `performance`: Performance optimizations

## 🔒 Security & Privacy

This repository should be kept **PRIVATE** due to:
- Client-specific implementation details
- Potential security configurations
- Business logic specifics

## 🤖 AI Assistant Guidelines

The AI assistant follows these Git practices:
1. **Always commit after successful changes**
2. **Use descriptive commit messages**
3. **Run self-diagnosis before commits**
4. **Create backups before risky operations**
5. **Maintain clean branch history**

## 📊 Branch Protection Rules

For production setup:
- Require pull request reviews
- Require status checks to pass
- Require branches to be up to date
- Restrict pushes to develop/main
- Require signed commits (optional)
EOF

echo ""
echo "🎉 Git integration setup complete!"
echo ""
echo "📋 Summary:"
echo "✅ Git repository initialized/configured"
echo "✅ Branching strategy implemented"
echo "✅ Git hooks installed (pre-commit, post-commit)"
echo "✅ AI assistant helper scripts created"
echo "✅ Commit message template configured"
echo "✅ Documentation created"
echo ""
echo "🔧 Next steps:"
echo "1. Set up remote repository (if desired)"
echo "2. Configure branch protection rules"
echo "3. Add team members (if applicable)"
echo "4. Run: ./scripts/git-ai-helper.sh health-check"
echo ""
echo "💡 AI Assistant tip:"
echo "Always run 'git status' before making changes and commit frequently!"
