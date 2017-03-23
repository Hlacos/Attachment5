# Attachment bundle

Laravel 5 bundle for store simply file attachments.

It's under development, not recommended for production use!

# Installation

1. add bundle to composer: "hlacos/attachment5": "dev-master"
2. composer install / update
3. add service provider to the providers list: 'Hlacos\Attachment5\Attachment5ServiceProvider'
4. publish config and migration: php artisan vendor:publish --provider="Hlacos\Attachment5\Attachment5ServiceProvider"
4. php artisan migrate
5. create directory: public/attachments
6. let it write by the web server

Attachments storing in public/attachments directory.
To override it:

2. edit config/attachment5.php

# Usage

<pre>
$attachment = new Attachment;
$attachment->addFile($filename);
$attachment->attachable()->associate($relatedModel);
$attachment->save();
</pre>

## Override table

Extend Hlacos\Attachment5\Models\Attachment and set the $table attribute.

## Set uploadable image required sizes

Extend Hlacos\Attachment5\Models\Attachment and set the $sizes array attribute.
In the array sets the width of the required images;

# Related models

You can set polymoprhic relations in the realted models.

<pre>
public function attachment() {
    return $this->morphOne('Hlacos\Attachment5\Models\Attachment', 'attachable');
}
</pre>

<pre>
public function attachment() {
    return $this->morphMany('Hlacos\Attachment5\Models\Attachment', 'attachable');
}
</pre>

# Contributions

Thanks to David Beyaty (https://github.com/hatja) for the gif resize implementation.
