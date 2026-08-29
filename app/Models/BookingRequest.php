<?php

namespace App\Models;

use App\Enums\BookingRequestEventType;
use App\Enums\BookingStatus;
use App\Enums\CustomerType;
use App\Support\Models\HasPublicIdentifier;
use Database\Factories\BookingRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'entertainer_id',
    'customer_id',
    'skill_id',
    'customer_type',
    'name',
    'company_name',
    'email',
    'phone',
    'event_date',
    'start_time',
    'end_time',
    'address',
    'postal_code',
    'city',
    'event_region',
    'travel_time_minutes',
    'children_count',
    'children_ages',
    'desired_skills',
    'selected_package',
    'selected_extras',
    'message',
    'quote_accepted_at',
    'quote_acceptance_name',
    'quote_acceptance_ip',
    'quote_acceptance_user_agent',
    'agreement_accepted_at',
    'agreement_version',
    'agreement_hash',
    'quote_performance_cents',
    'quote_travel_cents',
    'quote_total_cents',
    'deposit_cents',
    'paid_cents',
    'payment_status',
    'payment_reference',
    'payment_due_at',
    'invoice_status',
    'invoice_provider',
    'invoice_reference',
    'invoice_external_id',
    'invoice_url',
    'invoice_generated_at',
    'payment_provider',
    'payment_external_id',
    'payment_checkout_url',
    'payment_checkout_created_at',
    'cash_payment_allowed',
    'payment_instruction_sent_at',
    'calendar_synced_at',
    'calendar_sync_status',
    'quote_travel_distance_km',
    'quote_valid_until',
    'quote_sent_at',
    'quote_acceptance_token',
    'quote_terms_version',
    'quote_terms_body',
    'internal_note',
    'customer_message',
    'cancelled_at',
    'cancelled_by',
    'cancellation_reason',
    'last_reminder_sent_at',
    'last_notification_sent_at',
    'last_notification_status',
    'reminder_flags',
    'calendar_external_id',
    'customer_selection_token',
    'customer_selection_expires_at',
    'price_indication_min_cents',
    'price_indication_max_cents',
    'price_indication_currency',
    'price_indication_breakdown',
])]
class BookingRequest extends Model
{
    /** @use HasFactory<BookingRequestFactory> */
    use HasFactory, HasPublicIdentifier, SoftDeletes;

    public function entertainer(): BelongsTo
    {
        return $this->belongsTo(Entertainer::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(BookingRequestMatch::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(BookingRequestEvent::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function isGeneral(): bool
    {
        return $this->entertainer_id === null && $this->skill_id !== null;
    }

    public function customerSelectionUrl(): ?string
    {
        if (! $this->customer_selection_token) {
            return null;
        }

        return route('booking-requests.matches.index', [
            'bookingRequest' => $this,
            'token' => $this->customer_selection_token,
        ]);
    }

    protected static function booted(): void
    {
        static::created(function (BookingRequest $bookingRequest): void {
            $bookingRequest->events()->create([
                'type' => BookingRequestEventType::System,
                'actor_type' => 'system',
                'actor_name' => 'Platform',
                'body' => 'Aanvraag aangemaakt.',
                'new_status' => $bookingRequest->status,
                'visible_to_entertainer' => true,
                'visible_to_customer' => true,
            ]);

            if (filled($bookingRequest->message)) {
                $bookingRequest->events()->create([
                    'type' => BookingRequestEventType::CustomerMessage,
                    'actor_type' => 'customer',
                    'actor_name' => $bookingRequest->name,
                    'body' => $bookingRequest->message,
                    'visible_to_entertainer' => true,
                    'visible_to_customer' => true,
                ]);
            }

            if (filled($bookingRequest->internal_note)) {
                $bookingRequest->events()->create([
                    'type' => BookingRequestEventType::InternalNote,
                    'actor_type' => 'admin',
                    'actor_name' => auth()->user()?->name,
                    'body' => $bookingRequest->internal_note,
                    'visible_to_entertainer' => false,
                    'user_id' => auth()->id(),
                ]);
            }
        });

        static::updated(function (BookingRequest $bookingRequest): void {
            if ($bookingRequest->wasChanged('status')) {
                $bookingRequest->events()->create([
                    'type' => BookingRequestEventType::StatusChange,
                    'actor_type' => auth()->check() ? 'user' : 'system',
                    'actor_name' => auth()->user()?->name ?? 'Platform',
                    'old_status' => BookingStatus::tryFrom((string) $bookingRequest->getRawOriginal('status')),
                    'new_status' => $bookingRequest->status,
                    'visible_to_entertainer' => true,
                    'visible_to_customer' => true,
                    'user_id' => auth()->id(),
                ]);
            }

            if ($bookingRequest->wasChanged('message') && filled($bookingRequest->message)) {
                $bookingRequest->events()->create([
                    'type' => BookingRequestEventType::CustomerMessage,
                    'actor_type' => 'customer',
                    'actor_name' => $bookingRequest->name,
                    'body' => $bookingRequest->message,
                    'visible_to_entertainer' => true,
                    'visible_to_customer' => true,
                ]);
            }

            if ($bookingRequest->wasChanged('internal_note') && filled($bookingRequest->internal_note)) {
                $bookingRequest->events()->create([
                    'type' => BookingRequestEventType::InternalNote,
                    'actor_type' => 'admin',
                    'actor_name' => auth()->user()?->name,
                    'body' => $bookingRequest->internal_note,
                    'visible_to_entertainer' => false,
                    'user_id' => auth()->id(),
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'customer_type' => CustomerType::class,
            'skill_id' => 'integer',
            'event_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'travel_time_minutes' => 'integer',
            'children_count' => 'integer',
            'desired_skills' => 'array',
            'selected_extras' => 'array',
            'status' => BookingStatus::class,
            'quote_accepted_at' => 'datetime',
            'invoice_generated_at' => 'datetime',
            'payment_checkout_created_at' => 'datetime',
            'agreement_accepted_at' => 'datetime',
            'quote_performance_cents' => 'integer',
            'quote_travel_cents' => 'integer',
            'quote_total_cents' => 'integer',
            'deposit_cents' => 'integer',
            'paid_cents' => 'integer',
            'payment_due_at' => 'datetime',
            'cash_payment_allowed' => 'boolean',
            'payment_instruction_sent_at' => 'datetime',
            'calendar_synced_at' => 'datetime',
            'last_notification_sent_at' => 'datetime',
            'quote_travel_distance_km' => 'decimal:1',
            'quote_valid_until' => 'datetime',
            'quote_sent_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'last_reminder_sent_at' => 'datetime',
            'reminder_flags' => 'array',
            'customer_selection_expires_at' => 'datetime',
            'price_indication_min_cents' => 'integer',
            'price_indication_max_cents' => 'integer',
            'price_indication_breakdown' => 'array',
        ];
    }
}
