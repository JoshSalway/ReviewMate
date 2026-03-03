import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { index as customersIndex, store as customersStore, destroy as customersDestroy } from '@/routes/customers';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Customers',
        href: customersIndex(),
    },
];

type CustomerStatus = 'reviewed' | 'pending' | 'no_response' | 'no_request';

interface Customer {
    id: number;
    name: string;
    email: string;
    status: CustomerStatus;
    created_at: string;
}

interface PaginatedCustomers {
    data: Customer[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

interface Props {
    customers: PaginatedCustomers;
}

const statusConfig: Record<CustomerStatus, { label: string; className: string }> = {
    reviewed: { label: 'Reviewed', className: 'bg-green-100 text-green-700 hover:bg-green-100' },
    pending: { label: 'Pending', className: 'bg-yellow-100 text-yellow-700 hover:bg-yellow-100' },
    no_response: { label: 'No Response', className: 'bg-red-100 text-red-700 hover:bg-red-100' },
    no_request: { label: 'Not Sent', className: 'bg-gray-100 text-gray-600 hover:bg-gray-100' },
};

function StatusBadge({ status }: { status: CustomerStatus }) {
    const config = statusConfig[status] ?? statusConfig.no_request;
    return <Badge className={config.className}>{config.label}</Badge>;
}

export default function CustomersIndex({ customers }: Props) {
    const [showDialog, setShowDialog] = useState(false);
    const [deleteConfirm, setDeleteConfirm] = useState<number | null>(null);
    const [form, setForm] = useState({ name: '', email: '' });
    const [processing, setProcessing] = useState(false);

    const handleAddCustomer = () => {
        setProcessing(true);
        router.post(
            customersStore().url,
            form,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setShowDialog(false);
                    setForm({ name: '', email: '' });
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    const handleDelete = (id: number) => {
        router.delete(customersDestroy(id).url, {
            preserveScroll: true,
            onSuccess: () => setDeleteConfirm(null),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Customers" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Customers</h1>
                        <p className="mt-1 text-sm text-gray-500">Manage your customer list and review requests</p>
                    </div>
                    <Button
                        className="bg-teal-600 hover:bg-teal-700 text-white"
                        onClick={() => setShowDialog(true)}
                    >
                        + Add Customer
                    </Button>
                </div>

                <Card>
                    <CardContent className="p-0">
                        {customers.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-16 text-center">
                                <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                                    <svg className="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                    </svg>
                                </div>
                                <h3 className="mb-1 text-base font-semibold text-gray-900">No customers yet</h3>
                                <p className="mb-4 text-sm text-gray-500">Add your first customer to start sending review requests.</p>
                                <Button
                                    className="bg-teal-600 hover:bg-teal-700 text-white"
                                    onClick={() => setShowDialog(true)}
                                >
                                    Add Customer
                                </Button>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Added</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {customers.data.map((customer) => (
                                        <TableRow key={customer.id}>
                                            <TableCell className="font-medium">{customer.name}</TableCell>
                                            <TableCell className="text-gray-500">{customer.email}</TableCell>
                                            <TableCell>
                                                <StatusBadge status={customer.status} />
                                            </TableCell>
                                            <TableCell className="text-gray-500">
                                                {new Date(customer.created_at).toLocaleDateString()}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {deleteConfirm === customer.id ? (
                                                    <div className="flex items-center justify-end gap-2">
                                                        <span className="text-sm text-gray-500">Are you sure?</span>
                                                        <Button
                                                            size="sm"
                                                            variant="destructive"
                                                            onClick={() => handleDelete(customer.id)}
                                                        >
                                                            Delete
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => setDeleteConfirm(null)}
                                                        >
                                                            Cancel
                                                        </Button>
                                                    </div>
                                                ) : (
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        className="text-red-500 hover:text-red-700 hover:bg-red-50"
                                                        onClick={() => setDeleteConfirm(customer.id)}
                                                    >
                                                        Delete
                                                    </Button>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                {/* Pagination */}
                {customers.links.length > 3 && (
                    <div className="flex items-center justify-center gap-1">
                        {customers.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                className={link.active ? 'bg-teal-600 hover:bg-teal-700 text-white' : ''}
                                onClick={() => link.url && router.visit(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>

            {/* Add Customer Dialog */}
            <Dialog open={showDialog} onOpenChange={setShowDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Add Customer</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4 py-2">
                        <div className="space-y-2">
                            <Label htmlFor="customer-name">Name</Label>
                            <Input
                                id="customer-name"
                                placeholder="Jane Smith"
                                value={form.name}
                                onChange={(e) => setForm({ ...form, name: e.target.value })}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="customer-email">Email</Label>
                            <Input
                                id="customer-email"
                                type="email"
                                placeholder="jane@example.com"
                                value={form.email}
                                onChange={(e) => setForm({ ...form, email: e.target.value })}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowDialog(false)}>
                            Cancel
                        </Button>
                        <Button
                            className="bg-teal-600 hover:bg-teal-700 text-white"
                            onClick={handleAddCustomer}
                            disabled={processing || !form.name || !form.email}
                        >
                            {processing ? 'Adding...' : 'Add Customer'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
