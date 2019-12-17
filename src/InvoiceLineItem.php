<?php

namespace Laravel\Cashier;

use Carbon\Carbon;
use Stripe\InvoiceLineItem as StripeInvoiceLineItem;

class InvoiceLineItem
{
    /**
     * The Stripe model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $owner;

    /**
     * The Stripe invoice line item instance.
     *
     * @var \Stripe\InvoiceLineItem
     */
    protected $item;

    /**
     * Create a new invoice line item instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $owner
     * @param  \Stripe\InvoiceLineItem  $item
     * @return void
     */
    public function __construct($owner, StripeInvoiceLineItem $item)
    {
        $this->owner = $owner;
        $this->item = $item;
    }

    /**
     * Get the total for the invoice line item.
     *
     * @return string
     */
    public function total()
    {
        return $this->formatAmount($this->amount);
    }

    /**
     * Get the total percentage of the default inclusive tax for the invoice line item.
     *
     * @return int|null
     */
    public function inclusiveTaxPercentage()
    {
        return $this->item->tax_amounts
            ? $this->calculateTaxPercentage(true)
            : null;
    }

    /**
     * Get the total percentage of the default exclusive tax for the invoice line item.
     *
     * @return int|null
     */
    public function exclusiveTaxPercentage()
    {
        return $this->item->tax_amounts
            ? $this->calculateTaxPercentage(false)
            : null;
    }

    /**
     * Calculate the total tax percentage for either the inclusive or exclusive tax.
     *
     * @param  bool  $inclusive
     * @return int
     */
    protected function calculateTaxPercentage($inclusive)
    {
        return (int) collect($this->item->tax_amounts)
            ->filter(function (array $taxAmount) use ($inclusive) {
                return $taxAmount['inclusive'] === (bool) $inclusive;
            })
            ->sum(function (array $taxAmount) {
                return $taxAmount['tax_rate']->percentage;
            });
    }

    /**
     * Get a human readable date for the start date.
     *
     * @return string
     */
    public function startDate()
    {
        if ($this->isSubscription()) {
            return $this->startDateAsCarbon()->toFormattedDateString();
        }
    }

    /**
     * Get a human readable date for the end date.
     *
     * @return string
     */
    public function endDate()
    {
        if ($this->isSubscription()) {
            return $this->endDateAsCarbon()->toFormattedDateString();
        }
    }

    /**
     * Get a Carbon instance for the start date.
     *
     * @return \Carbon\Carbon
     */
    public function startDateAsCarbon()
    {
        if ($this->isSubscription()) {
            return Carbon::createFromTimestampUTC($this->item->period->start);
        }
    }

    /**
     * Get a Carbon instance for the end date.
     *
     * @return \Carbon\Carbon
     */
    public function endDateAsCarbon()
    {
        if ($this->isSubscription()) {
            return Carbon::createFromTimestampUTC($this->item->period->end);
        }
    }

    /**
     * Determine if the invoice line item is for a subscription.
     *
     * @return bool
     */
    public function isSubscription()
    {
        return $this->item->type === 'subscription';
    }

    /**
     * Format the given amount into a displayable currency.
     *
     * @param  int  $amount
     * @return string
     */
    protected function formatAmount($amount)
    {
        return Cashier::formatAmount($amount, $this->item->currency);
    }

    /**
     * Get the underlying Stripe invoice line item.
     *
     * @return \Stripe\InvoiceLineItem
     */
    public function asStripeInvoiceLineItem()
    {
        return $this->item;
    }

    /**
     * Dynamically access the Stripe invoice line item instance.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->item->{$key};
    }
}
