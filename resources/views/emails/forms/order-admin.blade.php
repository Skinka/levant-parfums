<x-mail::message>
# {{ trans('forms.mail.order.admin.subject') }}

{{ trans('forms.mail.order.admin.intro') }}

@if ($s->subject)
**{{ trans('forms.fields.subject') }}:** {{ $s->subject->name ?? $s->subject->getKey() }}
@endif

**{{ trans('forms.fields.name') }}:** {{ $s->data['name'] ?? '—' }}
**{{ trans('forms.fields.phone') }}:** {{ $s->data['phone'] ?? '—' }}
**{{ trans('forms.fields.email') }}:** {{ $s->data['email'] ?? '—' }}
**{{ trans('forms.fields.qty') }}:** {{ $s->data['qty'] ?? '—' }}

@if (!empty($s->data['note']))
**{{ trans('forms.fields.note') }}:** {{ $s->data['note'] }}
@endif

<x-mail::subcopy>
{{ trans('forms.fields.locale') }}: {{ $s->locale }}
{{ trans('forms.fields.created_at') }}: {{ $s->created_at?->toDateTimeString() }}
</x-mail::subcopy>
</x-mail::message>
