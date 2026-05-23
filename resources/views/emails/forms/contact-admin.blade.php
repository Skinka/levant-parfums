<x-mail::message>
# {{ trans('forms.mail.contact.admin.subject') }}

{{ trans('forms.mail.contact.admin.intro') }}

**{{ trans('forms.fields.name') }}:** {{ $s->data['name'] ?? '—' }}
**{{ trans('forms.fields.email') }}:** {{ $s->data['email'] ?? '—' }}

**{{ trans('forms.fields.message') }}:**

{{ $s->data['message'] ?? '' }}

<x-mail::subcopy>
{{ trans('forms.fields.locale') }}: {{ $s->locale }}
{{ trans('forms.fields.created_at') }}: {{ $s->created_at?->toDateTimeString() }}
</x-mail::subcopy>
</x-mail::message>
