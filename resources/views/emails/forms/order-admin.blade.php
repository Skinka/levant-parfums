@php($isPreorder = (bool) ($s->data['is_preorder'] ?? false))
@php($key = $isPreorder ? 'preorder' : 'order')
<x-mail::message>
# {{ trans("forms.mail.{$key}.admin.subject") }}

{{ trans("forms.mail.{$key}.admin.intro") }}

@if($isPreorder)
> {{ trans('forms.order.preorder_admin_notice') }}
@endif

**{{ trans('forms.fields.name') }}:** {{ $s->data['name'] ?? '' }}
**{{ trans('forms.fields.phone') }}:** {{ $s->data['phone'] ?? '' }}
**{{ trans('forms.fields.email') }}:** {{ $s->data['email'] ?? '' }}
**{{ trans('forms.fields.city') }}:** {{ $s->data['city'] ?? '' }}
**{{ trans('forms.fields.np_office') }}:** {{ $s->data['np_office'] ?? '' }}
**{{ trans('forms.fields.qty') }}:** {{ $s->data['qty'] ?? '' }}

@if(! empty($s->data['comment']))
**{{ trans('forms.fields.comment') }}:**
{{ $s->data['comment'] }}
@endif

@if($s->subject)
---
**{{ trans('forms.fields.subject') }}:** {{ $s->subject->name ?? ($s->subject->title ?? $s->subject->getKey()) }}
@endif

<x-mail::subcopy>
{{ trans('forms.fields.locale') }}: {{ $s->locale }}
{{ trans('forms.fields.created_at') }}: {{ $s->created_at?->toDateTimeString() }}
</x-mail::subcopy>
</x-mail::message>
