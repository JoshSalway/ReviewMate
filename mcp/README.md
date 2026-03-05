# ReviewMate MCP Server

A Model Context Protocol (MCP) server that exposes ReviewMate's review management capabilities to AI assistants (Claude Desktop, Cursor, etc.).

## Tools

| Tool | Description |
|------|-------------|
| `list_businesses` | List all businesses for the authenticated user |
| `get_business` | Get details for a business by ID |
| `get_review_stats` | Get review statistics (rating, conversion rate, pending replies) |
| `list_review_requests` | List review requests with status |
| `create_review_request` | Send a review request to a customer |
| `list_businesses_customers` | List customers for a business |
| `list_reviews` | List synced Google reviews |

## Setup

### 1. Install dependencies

```bash
cd mcp
npm install
```

### 2. Build

```bash
npm run build
```

### 3. Get an API token

Sign in to ReviewMate, then go to your account settings or use the API:

```bash
curl -X POST https://yourapp.com/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -H "Cookie: <your session cookie>" \
  -d '{"token_name": "MCP"}'
```

### 4. Configure Claude Desktop

Add to `~/Library/Application Support/Claude/claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "reviewmate": {
      "command": "node",
      "args": ["/absolute/path/to/ReviewMate/mcp/dist/index.js"],
      "env": {
        "REVIEWMATE_API_URL": "https://yourapp.com/api/v1",
        "REVIEWMATE_API_TOKEN": "your-token-here"
      }
    }
  }
}
```

### 5. Local development

```bash
REVIEWMATE_API_URL=http://localhost:8000/api/v1 REVIEWMATE_API_TOKEN=your-token npm run dev
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `REVIEWMATE_API_URL` | `http://localhost:8000/api/v1` | ReviewMate API base URL |
| `REVIEWMATE_API_TOKEN` | (required) | Sanctum API token |

## Example usage with Claude

Once configured, you can ask Claude:

- "List my ReviewMate businesses"
- "What are the review stats for business 1?"
- "Send a review request to customer 42 in business 1 via email"
- "How many reviews are pending a reply for my cafe?"
