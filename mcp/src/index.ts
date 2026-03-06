import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import type {
    Tool} from '@modelcontextprotocol/sdk/types.js';
import {
    CallToolRequestSchema,
    ListToolsRequestSchema
} from '@modelcontextprotocol/sdk/types.js';
import { z } from 'zod';

const BASE_URL = process.env.REVIEWMATE_API_URL ?? 'http://localhost:8000/api/v1';
const API_TOKEN = process.env.REVIEWMATE_API_TOKEN ?? '';

async function apiRequest<T>(
    method: string,
    path: string,
    body?: Record<string, unknown>,
): Promise<T> {
    const response = await fetch(`${BASE_URL}${path}`, {
        method,
        headers: {
            'Authorization': `Bearer ${API_TOKEN}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: body ? JSON.stringify(body) : undefined,
    });

    if (!response.ok) {
        const error = await response.text();
        throw new Error(`ReviewMate API error ${response.status}: ${error}`);
    }

    return response.json() as Promise<T>;
}

const tools: Tool[] = [
    {
        name: 'list_businesses',
        description: 'List all businesses belonging to the authenticated ReviewMate user.',
        inputSchema: {
            type: 'object',
            properties: {},
            required: [],
        },
    },
    {
        name: 'get_business',
        description: 'Get details for a specific business by ID, including Google rating and conversion rate.',
        inputSchema: {
            type: 'object',
            properties: {
                business_id: {
                    type: 'number',
                    description: 'The numeric ID of the business.',
                },
            },
            required: ['business_id'],
        },
    },
    {
        name: 'get_review_stats',
        description: 'Get review statistics for a business: total reviews, average rating, conversion rate, pending replies.',
        inputSchema: {
            type: 'object',
            properties: {
                business_id: {
                    type: 'number',
                    description: 'The numeric ID of the business.',
                },
            },
            required: ['business_id'],
        },
    },
    {
        name: 'list_review_requests',
        description: 'List review requests sent by a business, with status (sent/opened/reviewed) and channel.',
        inputSchema: {
            type: 'object',
            properties: {
                business_id: {
                    type: 'number',
                    description: 'The numeric ID of the business.',
                },
            },
            required: ['business_id'],
        },
    },
    {
        name: 'create_review_request',
        description: 'Send a review request to a customer. The 30-day duplicate guard will reject requests if one was already sent recently.',
        inputSchema: {
            type: 'object',
            properties: {
                business_id: {
                    type: 'number',
                    description: 'The numeric ID of the business.',
                },
                customer_id: {
                    type: 'number',
                    description: 'The numeric ID of the customer.',
                },
                channel: {
                    type: 'string',
                    enum: ['email', 'sms', 'both'],
                    description: 'The channel to send the review request via.',
                },
            },
            required: ['business_id', 'customer_id', 'channel'],
        },
    },
    {
        name: 'list_businesses_customers',
        description: 'List customers for a business, paginated. Returns name, email, phone, request status, and unsubscribe status.',
        inputSchema: {
            type: 'object',
            properties: {
                business_id: {
                    type: 'number',
                    description: 'The numeric ID of the business.',
                },
            },
            required: ['business_id'],
        },
    },
    {
        name: 'list_reviews',
        description: 'List Google reviews synced for a business, with rating, body, reviewer name, and reply status.',
        inputSchema: {
            type: 'object',
            properties: {
                business_id: {
                    type: 'number',
                    description: 'The numeric ID of the business.',
                },
            },
            required: ['business_id'],
        },
    },
];

const GetBusinessSchema = z.object({ business_id: z.number() });
const CreateReviewRequestSchema = z.object({
    business_id: z.number(),
    customer_id: z.number(),
    channel: z.enum(['email', 'sms', 'both']),
});
const ListByBusinessSchema = z.object({ business_id: z.number() });

const server = new Server(
    {
        name: 'reviewmate-mcp',
        version: '1.0.0',
    },
    {
        capabilities: {
            tools: {},
        },
    },
);

server.setRequestHandler(ListToolsRequestSchema, async () => ({
    tools,
}));

server.setRequestHandler(CallToolRequestSchema, async (request) => {
    const { name, arguments: args } = request.params;

    try {
        switch (name) {
            case 'list_businesses': {
                const data = await apiRequest('GET', '/businesses');
                return {
                    content: [{ type: 'text', text: JSON.stringify(data, null, 2) }],
                };
            }

            case 'get_business': {
                const { business_id } = GetBusinessSchema.parse(args);
                const data = await apiRequest('GET', `/businesses/${business_id}`);
                return {
                    content: [{ type: 'text', text: JSON.stringify(data, null, 2) }],
                };
            }

            case 'get_review_stats': {
                const { business_id } = GetBusinessSchema.parse(args);
                const data = await apiRequest('GET', `/businesses/${business_id}/stats`);
                return {
                    content: [{ type: 'text', text: JSON.stringify(data, null, 2) }],
                };
            }

            case 'list_review_requests': {
                const { business_id } = ListByBusinessSchema.parse(args);
                const data = await apiRequest('GET', `/businesses/${business_id}/review-requests`);
                return {
                    content: [{ type: 'text', text: JSON.stringify(data, null, 2) }],
                };
            }

            case 'create_review_request': {
                const { business_id, customer_id, channel } = CreateReviewRequestSchema.parse(args);
                const data = await apiRequest('POST', `/businesses/${business_id}/review-requests`, {
                    customer_id,
                    channel,
                });
                return {
                    content: [{ type: 'text', text: JSON.stringify(data, null, 2) }],
                };
            }

            case 'list_businesses_customers': {
                const { business_id } = ListByBusinessSchema.parse(args);
                const data = await apiRequest('GET', `/businesses/${business_id}/customers`);
                return {
                    content: [{ type: 'text', text: JSON.stringify(data, null, 2) }],
                };
            }

            case 'list_reviews': {
                const { business_id } = ListByBusinessSchema.parse(args);
                const data = await apiRequest('GET', `/businesses/${business_id}/reviews`);
                return {
                    content: [{ type: 'text', text: JSON.stringify(data, null, 2) }],
                };
            }

            default:
                throw new Error(`Unknown tool: ${name}`);
        }
    } catch (error) {
        const message = error instanceof Error ? error.message : String(error);
        return {
            content: [{ type: 'text', text: `Error: ${message}` }],
            isError: true,
        };
    }
});

async function main() {
    const transport = new StdioServerTransport();
    await server.connect(transport);
}

main().catch((error) => {
    process.stderr.write(`Fatal error: ${error}\n`);
    process.exit(1);
});
