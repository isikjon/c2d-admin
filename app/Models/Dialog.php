<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dialog extends Model
{
    // Статусы диалога (генерируются AI-агентом)
    public const STATUS_NEW = 'new';
    public const STATUS_PRESENTATION = 'presentation';
    public const STATUS_BUILDING_COMMUNICATION = 'building_communication';
    public const STATUS_PRODUCT_QUESTIONS = 'product_questions';
    public const STATUS_OBJECTION_HANDLING = 'objection_handling';
    public const STATUS_CONTACT_LATER = 'contact_later';
    public const STATUS_READY_TO_PROCESS = 'ready_to_process';
    public const STATUS_PROVIDED_ALL_DATA = 'provided_all_data';
    public const STATUS_PROVIDED_PARTIAL_DATA = 'provided_partial_data';
    public const STATUS_NOT_PROVIDED_DATA = 'not_provided_data';
    public const STATUS_CALL_MANAGER = 'call_manager';
    public const STATUS_STRONG_NEGATIVE = 'strong_negative';
    public const STATUS_POLICY_ISSUED = 'policy_issued';
    public const STATUS_REFUSED_TO_ISSUE = 'refused_to_issue';
    public const STATUS_CROSS_SALE = 'cross_sale';
    public const STATUS_CONSULTATION_REQUEST = 'consultation_request';
    public const STATUS_NO_RESPONSE = 'no_response';

    // Статусы мониторинга (системные)
    public const STATUS_WITH_RESPONSES = 'with_responses';
    public const STATUS_WITHOUT_RESPONSES = 'without_responses';
    public const STATUS_NOT_DELIVERED = 'not_delivered';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_READ = 'read';
    public const STATUS_UNREAD = 'unread';
    public const STATUS_GOAL_COMPLETED = 'goal_completed';
    public const STATUS_GOAL_NOT_COMPLETED = 'goal_not_completed';
    public const STATUS_LINK_CLICKED = 'link_clicked';

    // Статусы для отправки в CRM
    public const CRM_STATUSES = [
        self::STATUS_READY_TO_PROCESS,
        self::STATUS_PROVIDED_ALL_DATA,
        self::STATUS_PROVIDED_PARTIAL_DATA,
        self::STATUS_NOT_PROVIDED_DATA,
        self::STATUS_CALL_MANAGER,
        self::STATUS_STRONG_NEGATIVE,
        self::STATUS_POLICY_ISSUED,
        self::STATUS_REFUSED_TO_ISSUE,
        self::STATUS_CROSS_SALE,
        self::STATUS_CONSULTATION_REQUEST,
    ];

    // Все допустимые статусы
    public const ALL_STATUSES = [
        self::STATUS_NEW,
        self::STATUS_PRESENTATION,
        self::STATUS_BUILDING_COMMUNICATION,
        self::STATUS_PRODUCT_QUESTIONS,
        self::STATUS_OBJECTION_HANDLING,
        self::STATUS_CONTACT_LATER,
        self::STATUS_READY_TO_PROCESS,
        self::STATUS_PROVIDED_ALL_DATA,
        self::STATUS_PROVIDED_PARTIAL_DATA,
        self::STATUS_NOT_PROVIDED_DATA,
        self::STATUS_CALL_MANAGER,
        self::STATUS_STRONG_NEGATIVE,
        self::STATUS_POLICY_ISSUED,
        self::STATUS_REFUSED_TO_ISSUE,
        self::STATUS_CROSS_SALE,
        self::STATUS_CONSULTATION_REQUEST,
        self::STATUS_NO_RESPONSE,
        self::STATUS_WITH_RESPONSES,
        self::STATUS_WITHOUT_RESPONSES,
        self::STATUS_NOT_DELIVERED,
        self::STATUS_BLOCKED,
        self::STATUS_READ,
        self::STATUS_UNREAD,
        self::STATUS_GOAL_COMPLETED,
        self::STATUS_GOAL_NOT_COMPLETED,
        self::STATUS_LINK_CLICKED,
    ];

    protected $fillable = [
        'client_id', 'campaign_id', 'channel', 'external_chat_id', 'external_id',
        'current_status', 'is_ai_active', 'messages_count',
        'last_message_at', 'last_client_message_at', 'closed_at',
    ];

    protected $casts = [
        'is_ai_active' => 'boolean',
        'last_message_at' => 'datetime',
        'last_client_message_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(DialogStatus::class)->orderBy('created_at', 'desc');
    }

    public function updateStatus(string $newStatus, string $source = 'system', array $metadata = []): void
    {
        $previousStatus = $this->current_status;

        DialogStatus::create([
            'dialog_id' => $this->id,
            'status' => $newStatus,
            'previous_status' => $previousStatus,
            'source' => $source,
            'metadata' => $metadata,
        ]);

        $this->update(['current_status' => $newStatus]);
    }

    public function shouldSendToCrm(): bool
    {
        return in_array($this->current_status, self::CRM_STATUSES);
    }

    public static function getStatusLabel(string $status): string
    {
        $labels = [
            self::STATUS_NEW => 'Новый',
            self::STATUS_PRESENTATION => 'Презентация',
            self::STATUS_BUILDING_COMMUNICATION => 'Налаживание коммуникации',
            self::STATUS_PRODUCT_QUESTIONS => 'Вопросы по продукту',
            self::STATUS_OBJECTION_HANDLING => 'Отработка возражений',
            self::STATUS_CONTACT_LATER => 'Написать позже',
            self::STATUS_READY_TO_PROCESS => 'Готов оформлять',
            self::STATUS_PROVIDED_ALL_DATA => 'Предоставил все данные',
            self::STATUS_PROVIDED_PARTIAL_DATA => 'Предоставил часть данных',
            self::STATUS_NOT_PROVIDED_DATA => 'Не предоставил данные',
            self::STATUS_CALL_MANAGER => 'Вызов менеджера',
            self::STATUS_STRONG_NEGATIVE => 'Сильный негатив',
            self::STATUS_POLICY_ISSUED => 'Оформил полис',
            self::STATUS_REFUSED_TO_ISSUE => 'Отказался оформлять полис',
            self::STATUS_CROSS_SALE => 'Кросс-продажа',
            self::STATUS_CONSULTATION_REQUEST => 'Запросил консультацию по доп продукту',
            self::STATUS_NO_RESPONSE => 'Не отвечает',
            self::STATUS_WITH_RESPONSES => 'С ответами',
            self::STATUS_WITHOUT_RESPONSES => 'Без ответов',
            self::STATUS_NOT_DELIVERED => 'Не дошли',
            self::STATUS_BLOCKED => 'Заблокированные',
            self::STATUS_READ => 'Прочитанные',
            self::STATUS_UNREAD => 'Не прочитанные',
            self::STATUS_GOAL_COMPLETED => 'Выполнена цель',
            self::STATUS_GOAL_NOT_COMPLETED => 'Не выполнена цель',
            self::STATUS_LINK_CLICKED => 'Произошел переход по ссылке',
        ];

        return $labels[$status] ?? $status;
    }
}
