<x-mail::message>
# {{ trans('forms.mail.order.client.subject') }}

{{ trans('forms.mail.order.client.intro') }}

**{{ trans('forms.fields.name') }}:** {{ $s->data['name'] ?? '—' }}
@if ($s->subject)
**{{ trans('forms.fields.subject') }}:** {{ $s->subject->name ?? $s->subject->getKey() }}
@endif
**{{ trans('forms.fields.qty') }}:** {{ $s->data['qty'] ?? '—' }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
