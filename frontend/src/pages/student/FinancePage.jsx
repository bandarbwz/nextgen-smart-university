import { useEffect, useState } from 'react';
import { AlertTriangle, Award, CheckCircle2, Receipt, Wallet } from 'lucide-react';
import { PageHeader } from '../../components/PageHeader';
import { EmptyState } from '../../components/EmptyState';
import { SkeletonRows } from '../../components/Skeleton';
import { Badge } from '../../components/Badge';
import { Alert } from '../../components/Alert';
import { financeService } from '../../services/financeService';
import { readApiError } from '../../services/apiClient';

const invoiceVariants = {
    Paid: 'success',
    Pending: 'warning',
    'Partially Paid': 'warning',
    Overdue: 'danger',
    Cancelled: 'neutral',
};

function money(value) {
    return Number(value).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

export function FinancePage() {
    const [invoices, setInvoices] = useState([]);
    const [payments, setPayments] = useState([]);
    const [scholarships, setScholarships] = useState([]);
    const [standing, setStanding] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [notice, setNotice] = useState('');

    useEffect(() => {
        Promise.all([
            financeService.invoices(),
            financeService.payments(),
            financeService.scholarships(),
            financeService.standing(),
        ])
            .then(([invoiceData, paymentData, scholarshipData, standingData]) => {
                setInvoices(invoiceData);
                setPayments(paymentData);
                setScholarships(scholarshipData);
                setStanding(standingData);
            })
            .catch((error) => setNotice(readApiError(error, 'Unable to load your finances.').message))
            .finally(() => setIsLoading(false));
    }, []);

    if (isLoading) {
        return (
            <>
                <PageHeader title="Finance" subtitle="Your invoices, payments and scholarships." />
                <SkeletonRows rows={4} height={80} />
            </>
        );
    }

    return (
        <>
            <PageHeader title="Finance" subtitle="Your invoices, payments and scholarships." />

            {notice && <Alert variant="error">{notice}</Alert>}

            {standing && !standing.can_register && (
                <Alert variant="error">
                    {standing.active_hold
                        ? `A financial hold is active on your account: ${standing.active_hold.reason}. You cannot register for courses until it is released.`
                        : 'You have an overdue invoice. You cannot register for courses until it is settled.'}
                </Alert>
            )}

            {standing && (
                <div className="nsu-grid nsu-grid--stats" style={{ marginBottom: 'var(--space-xl)' }}>
                    <article className="nsu-card">
                        <div className="nsu-stat">
                            <div>
                                <p className="nsu-stat__label">Outstanding balance</p>
                                <p className="nsu-stat__value tabular">
                                    {money(standing.outstanding_balance)}
                                </p>
                            </div>
                            <span className="nsu-stat__icon">
                                <Wallet size={20} aria-hidden="true" />
                            </span>
                        </div>
                    </article>

                    <article className="nsu-card">
                        <div className="nsu-stat">
                            <div>
                                <p className="nsu-stat__label">Registration standing</p>
                                <p className="nsu-stat__value" style={{ fontSize: 'var(--text-lg)' }}>
                                    {standing.can_register ? 'Clear' : 'Blocked'}
                                </p>
                                <p className="nsu-stat__hint">
                                    {standing.has_overdue_invoice
                                        ? 'An invoice is past its due date'
                                        : 'No overdue invoices'}
                                </p>
                            </div>
                            <span className="nsu-stat__icon">
                                {standing.can_register ? (
                                    <CheckCircle2 size={20} aria-hidden="true" />
                                ) : (
                                    <AlertTriangle size={20} aria-hidden="true" />
                                )}
                            </span>
                        </div>
                    </article>
                </div>
            )}

            <h2 className="nsu-section-title">Invoices</h2>

            <div className="nsu-card" style={{ marginBottom: 'var(--space-xl)' }}>
                {invoices.length === 0 ? (
                    <EmptyState
                        icon={Receipt}
                        title="No invoices yet"
                        description="Invoices appear here once the finance office issues them for the semester."
                    />
                ) : (
                    <div className="nsu-table-wrap">
                        <table className="nsu-table">
                            <caption className="visually-hidden">Your invoices</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Invoice</th>
                                    <th scope="col">Semester</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Paid</th>
                                    <th scope="col">Balance</th>
                                    <th scope="col">Due</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {invoices.map((invoice) => (
                                    <tr key={invoice.id}>
                                        <td className="tabular">{invoice.invoice_number}</td>
                                        <td>{invoice.semester_name}</td>
                                        <td className="tabular">{money(invoice.total_amount)}</td>
                                        <td className="tabular">{money(invoice.paid_amount)}</td>
                                        <td className="tabular">{money(invoice.balance)}</td>
                                        <td className="tabular">{invoice.due_date}</td>
                                        <td>
                                            <Badge variant={invoiceVariants[invoice.status] ?? 'neutral'}>
                                                {invoice.status}
                                            </Badge>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            <h2 className="nsu-section-title">Payment history</h2>

            <div className="nsu-card" style={{ marginBottom: 'var(--space-xl)' }}>
                {payments.length === 0 ? (
                    <EmptyState
                        icon={Wallet}
                        title="No payments recorded"
                        description="Payments recorded by the finance office appear here."
                    />
                ) : (
                    <div className="nsu-table-wrap">
                        <table className="nsu-table">
                            <caption className="visually-hidden">Your payment history</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Reference</th>
                                    <th scope="col">Invoice</th>
                                    <th scope="col">Method</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                {payments.map((payment) => (
                                    <tr key={payment.id}>
                                        <td className="tabular">{payment.payment_reference}</td>
                                        <td className="tabular">{payment.invoice_number}</td>
                                        <td>{payment.payment_method}</td>
                                        <td className="tabular">{money(payment.amount)}</td>
                                        <td className="tabular">{payment.payment_date}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            <h2 className="nsu-section-title">Scholarships</h2>

            <div className="nsu-card">
                {scholarships.length === 0 ? (
                    <EmptyState
                        icon={Award}
                        title="No scholarships"
                        description="Awarded scholarships appear here and reduce your invoiced amount."
                    />
                ) : (
                    <div className="nsu-table-wrap">
                        <table className="nsu-table">
                            <caption className="visually-hidden">Your scholarships</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">From</th>
                                    <th scope="col">To</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {scholarships.map((scholarship) => (
                                    <tr key={scholarship.id}>
                                        <td>{scholarship.scholarship_name}</td>
                                        <td className="tabular">{money(scholarship.amount)}</td>
                                        <td className="tabular">{scholarship.start_date}</td>
                                        <td className="tabular">{scholarship.end_date}</td>
                                        <td>
                                            <Badge
                                                variant={
                                                    scholarship.status === 'active' ? 'success' : 'neutral'
                                                }
                                            >
                                                {scholarship.status}
                                            </Badge>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
