<x-mail.client-layout
    :message="$message ?? null"
    :siteName="$siteName ?? get_site_name()"
    :logoUrl="$logoUrl ?? null"
    :title="$title ?? ($siteName ?? get_site_name())"
>
    {!! $content !!}
</x-mail.client-layout>
