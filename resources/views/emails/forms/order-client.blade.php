@php($isPreorder = (bool) ($s->data['is_preorder'] ?? false))
@php($key = $isPreorder ? 'preorder' : 'order')
<x-mail::message>
# {{ trans("forms.mail.{$key}.client.subject") }}

{{ trans("forms.mail.{$key}.client.intro") }}

@if($isPreorder)
> {{ trans('forms.order.preorder_client_notice') }}
@endif

— LEVANT Parfums
</x-mail::message>
