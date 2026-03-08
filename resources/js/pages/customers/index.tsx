import { Head, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { index as customersIndex, store as customersStore, destroy as customersDestroy, bulkSend as customersBulkSend, exportMethod as customersExport, importMethod as customersImport } from '@/routes/customers';
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

interface CsvRow {
    name: string;
    email: string;
    phone: string;
}

const statusConfig: Record<CustomerStatus, { label: string; className: string }> = {
    reviewed: { label: 'Reviewed', className: 'bg-green-100 text-green-700 hover:bg-green-100' },
    pending: { label: 'Pending', className: 'bg-yellow-100 text-yellow-700 hover:bg-yellow-100' },
    no_response: { label: 'No Response', className: 'bg-red-100 text-red-700 hover:bg-red-100' },
    no_request: { label: 'Ready to request', className: 'bg-muted text-muted-foreground hover:bg-muted' },
};

function StatusBadge({ status }: { status: CustomerStatus }) {
    const config = statusConfig[status] ?? statusConfig.no_request;
    return <Badge className={config.className}>{config.label}</Badge>;
}

const parseCsv = (text: string): CsvRow[] => {
    const lines = text.trim().split('\n');
    const headers = lines[0].toLowerCase().split(',').map(h => h.trim().replace(/"/g, ''));
    return lines.slice(1).map(line => {
        const values = line.split(',').map(v => v.trim().replace(/"/g, ''));
        const row: Record<string, string> = {};
        headers.forEach((h, i) => { row[h] = values[i] ?? ''; });
        return {
            name: row['name'] || row['full name'] || row['customer name'] || `${row['first name'] || ''} ${row['last name'] || ''}`.trim(),
            email: row['email'] || row['email address'] || '',
            phone: row['phone'] || row['mobile'] || row['phone number'] || row['mobile number'] || '',
        };
    }).filter(r => r.name || r.email);
};

const downloadSample = () => {
    const csv = 'name,email,phone\nJane Smith,jane@example.com,0412 345 678\nMark Jones,mark@example.com,0423 456 789\nSarah Brown,,0434 567 890';
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'customers-sample.csv';
    a.click();
    URL.revokeObjectURL(url);
};

function ImportCsvDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
    const [parsedRows, setParsedRows] = useState<CsvRow[]>([]);
    const [fileName, setFileName] = useState('');
    const [importing, setImporting] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        setFileName(file.name);
        const reader = new FileReader();
        reader.onload = (ev) => {
            const text = ev.target?.result as string;
            setParsedRows(parseCsv(text));
        };
        reader.readAsText(file);
    };

    const handleClose = () => {
        setParsedRows([]);
        setFileName('');
        setImporting(false);
        if (fileInputRef.current) fileInputRef.current.value = '';
        onClose();
    };

    const handleImport = () => {
        if (parsedRows.length === 0) return;
        setImporting(true);
        router.post(
            customersImport().url,
            { customers: parsedRows as unknown as Record<string, string>[] },
            {
                preserveScroll: true,
                onSuccess: () => handleClose(),
                onFinish: () => setImporting(false),
            },
        );
    };

    const previewRows = parsedRows.slice(0, 10);
    const remaining = parsedRows.length - 10;
    const validRows = parsedRows.filter(r => r.name || r.email);

    return (
        <Dialog open={open} onOpenChange={(v) => { if (!v) handleClose(); }}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Import Customers from CSV</DialogTitle>
                </DialogHeader>
                <div className="space-y-4 py-2">
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Upload a CSV file with columns: <span className="font-mono text-xs bg-muted px-1 py-0.5 rounded">name</span>, <span className="font-mono text-xs bg-muted px-1 py-0.5 rounded">email</span>, <span className="font-mono text-xs bg-muted px-1 py-0.5 rounded">phone</span>
                        </p>
                        <button
                            type="button"
                            className="text-sm text-teal-600 underline hover:text-teal-700"
                            onClick={downloadSample}
                        >
                            Download sample CSV
                        </button>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="csv-file">CSV File</Label>
                        <Input
                            id="csv-file"
                            type="file"
                            accept=".csv"
                            ref={fileInputRef}
                            onChange={handleFileChange}
                        />
                        {fileName && (
                            <p className="text-xs text-muted-foreground">Selected: {fileName}</p>
                        )}
                    </div>

                    {parsedRows.length > 0 && (
                        <div className="space-y-2">
                            <p className="text-sm font-medium text-foreground">
                                Ready to import <span className="text-teal-600 font-semibold">{validRows.length}</span> customer{validRows.length !== 1 ? 's' : ''}
                            </p>
                            <div className="rounded-md border overflow-hidden">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Name</TableHead>
                                            <TableHead>Email</TableHead>
                                            <TableHead>Phone</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {previewRows.map((row, i) => {
                                            const isInvalid = !row.name && !row.email;
                                            return (
                                                <TableRow key={i} className={isInvalid ? 'bg-red-50' : ''}>
                                                    <TableCell className={isInvalid ? 'text-red-500' : ''}>
                                                        {row.name || <span className="text-muted-foreground italic">—</span>}
                                                    </TableCell>
                                                    <TableCell className={isInvalid ? 'text-red-500' : 'text-muted-foreground'}>
                                                        {row.email || <span className="text-muted-foreground italic">—</span>}
                                                    </TableCell>
                                                    <TableCell className="text-muted-foreground">
                                                        {row.phone || <span className="italic">—</span>}
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            </div>
                            {remaining > 0 && (
                                <p className="text-xs text-muted-foreground">…and {remaining} more row{remaining !== 1 ? 's' : ''} not shown</p>
                            )}
                        </div>
                    )}
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={handleClose} disabled={importing}>
                        Cancel
                    </Button>
                    <Button
                        className="bg-teal-600 hover:bg-teal-700 text-white"
                        onClick={handleImport}
                        disabled={importing || validRows.length === 0}
                    >
                        {importing ? 'Importing...' : `Import ${validRows.length > 0 ? validRows.length : ''} customer${validRows.length !== 1 ? 's' : ''}`}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function CustomersIndex({ customers }: Props) {
    const { flash } = usePage<{ flash: { success?: string; error?: string } }>().props;
    const [showDialog, setShowDialog] = useState(false);
    const [showImportDialog, setShowImportDialog] = useState(false);
    const [deleteConfirm, setDeleteConfirm] = useState<number | null>(null);
    const [form, setForm] = useState({ name: '', email: '' });
    const [processing, setProcessing] = useState(false);
    const [selected, setSelected] = useState<number[]>([]);
    const [bulkChannel, setBulkChannel] = useState<'email' | 'sms' | 'both'>('email');
    const [bulkSending, setBulkSending] = useState(false);
    const [flashMessage, setFlashMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    useEffect(() => {
        if (flash?.success) {
            setFlashMessage({ type: 'success', text: flash.success });
            const timer = setTimeout(() => setFlashMessage(null), 5000);
            return () => clearTimeout(timer);
        }
        if (flash?.error) {
            setFlashMessage({ type: 'error', text: flash.error });
            const timer = setTimeout(() => setFlashMessage(null), 5000);
            return () => clearTimeout(timer);
        }
    }, [flash]);

    const allIds = customers.data.map((c) => c.id);
    const allSelected = allIds.length > 0 && allIds.every((id) => selected.includes(id));
    const someSelected = selected.length > 0;

    const toggleAll = () => {
        setSelected(allSelected ? [] : allIds);
    };

    const toggleOne = (id: number) => {
        setSelected((prev) =>
            prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id],
        );
    };

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

    const handleBulkSend = () => {
        if (selected.length === 0) return;
        setBulkSending(true);
        router.post(
            customersBulkSend().url,
            { customer_ids: selected, channel: bulkChannel },
            {
                preserveScroll: true,
                onSuccess: () => setSelected([]),
                onFinish: () => setBulkSending(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Customers" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">Customers</h1>
                        <p className="mt-1 text-sm text-muted-foreground">Add past customers and we'll ask them for a review — takes 2 minutes to set up.</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <a href={customersExport().url} download>
                            <Button variant="outline">
                                Export CSV
                            </Button>
                        </a>
                        <Button
                            variant="outline"
                            onClick={() => setShowImportDialog(true)}
                        >
                            Import CSV
                        </Button>
                        <Button
                            className="bg-teal-600 hover:bg-teal-700 text-white"
                            onClick={() => setShowDialog(true)}
                        >
                            + Add Customer
                        </Button>
                    </div>
                </div>

                {/* Flash message */}
                {flashMessage && (
                    <div className={`flex items-center gap-3 rounded-lg border px-4 py-3 ${flashMessage.type === 'success' ? 'border-teal-200 bg-teal-50' : 'border-red-200 bg-red-50'}`}>
                        {flashMessage.type === 'success' ? (
                            <svg className="h-5 w-5 text-teal-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        ) : (
                            <svg className="h-5 w-5 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        )}
                        <span className={`text-sm font-medium ${flashMessage.type === 'success' ? 'text-teal-700' : 'text-red-700'}`}>
                            {flashMessage.text}
                        </span>
                    </div>
                )}

                {/* Bulk action bar */}
                {someSelected && (
                    <div className="flex items-center gap-3 rounded-lg border border-teal-200 bg-teal-50 px-4 py-3">
                        <span className="text-sm font-medium text-teal-800">
                            {selected.length} customer{selected.length !== 1 ? 's' : ''} selected
                        </span>
                        <div className="ml-auto flex items-center gap-2">
                            <Select value={bulkChannel} onValueChange={(v: any) => setBulkChannel(v)}>
                                <SelectTrigger className="h-8 w-28 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="email">Email</SelectItem>
                                    <SelectItem value="sms">SMS</SelectItem>
                                    <SelectItem value="both">Both</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button
                                size="sm"
                                className="bg-teal-600 hover:bg-teal-700 text-white"
                                onClick={handleBulkSend}
                                disabled={bulkSending}
                            >
                                {bulkSending ? 'Sending...' : 'Send Review Request'}
                            </Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                className="text-muted-foreground"
                                onClick={() => setSelected([])}
                            >
                                Clear
                            </Button>
                        </div>
                    </div>
                )}

                <Card>
                    <CardContent className="p-0">
                        {customers.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-16 text-center">
                                <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                                    <svg className="h-8 w-8 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                    </svg>
                                </div>
                                <h3 className="mb-1 text-base font-semibold text-foreground">No customers yet</h3>
                                <p className="mb-4 text-sm text-muted-foreground">Add your first customer to start sending review requests.</p>
                                <Button
                                    className="bg-teal-600 hover:bg-teal-700 text-white"
                                    onClick={() => setShowDialog(true)}
                                >
                                    Add Customer
                                </Button>
                                <p className="mt-2 text-sm text-muted-foreground">Have a customer list? <button className="text-teal-600 underline hover:text-teal-700" onClick={() => setShowImportDialog(true)}>Import from CSV</button></p>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-10">
                                            <Checkbox
                                                checked={allSelected}
                                                onCheckedChange={toggleAll}
                                                aria-label="Select all"
                                            />
                                        </TableHead>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Added</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {customers.data.map((customer) => (
                                        <TableRow
                                            key={customer.id}
                                            className={selected.includes(customer.id) ? 'bg-teal-50/50' : ''}
                                        >
                                            <TableCell>
                                                <Checkbox
                                                    checked={selected.includes(customer.id)}
                                                    onCheckedChange={() => toggleOne(customer.id)}
                                                    aria-label={`Select ${customer.name}`}
                                                />
                                            </TableCell>
                                            <TableCell className="font-medium">{customer.name}</TableCell>
                                            <TableCell className="text-muted-foreground">{customer.email}</TableCell>
                                            <TableCell>
                                                <StatusBadge status={customer.status} />
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {new Date(customer.created_at).toLocaleDateString()}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {deleteConfirm === customer.id ? (
                                                    <div className="flex items-center justify-end gap-2">
                                                        <span className="text-sm text-muted-foreground">Are you sure?</span>
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
                                                    <div className="flex items-center justify-end gap-2">
                                                        {customer.status === 'no_request' && (
                                                            <Button
                                                                size="sm"
                                                                className="bg-teal-600 hover:bg-teal-700 text-white"
                                                                onClick={() => router.visit(`/quick-send?name=${encodeURIComponent(customer.name)}&email=${encodeURIComponent(customer.email)}`)}
                                                            >
                                                                Send request
                                                            </Button>
                                                        )}
                                                        {customer.status === 'no_response' && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => router.visit(`/quick-send?name=${encodeURIComponent(customer.name)}&email=${encodeURIComponent(customer.email)}`)}
                                                            >
                                                                Re-send
                                                            </Button>
                                                        )}
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            className="text-red-500 hover:text-red-700 hover:bg-red-50"
                                                            onClick={() => setDeleteConfirm(customer.id)}
                                                        >
                                                            Delete
                                                        </Button>
                                                    </div>
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

            {/* Import CSV Dialog */}
            <ImportCsvDialog open={showImportDialog} onClose={() => setShowImportDialog(false)} />
        </AppLayout>
    );
}
