# Prims47TranslateBundle

This bundle provides a translate databse solution for Symfony2.

## First step: Installation

Use composer to manage your dependencies and download Prims47TranslateBundle:

``` json
"require": {
    "php": ">=5.3.3",
    "symfony/symfony": ">=2.2",
    "doctrine/orm": "~2.2,>=2.2.3",
    "doctrine/doctrine-bundle": "~1.2",
    // ...
    "prims47/translate-bundle": "dev-master"
}
```

## Enable Prims47TranslateBundle

``` php
<?php
// app/AppKernel.php
public function registerBundles()
{
    return array(
        // ...
        // Then add Prims47TranslateBundle
        new Prims47\Bundle\TranslateBundle\Prims47TranslateBundle(),
        // ...
    );
}
```

## Configuration Prims47TranslateBundle

### Configure your locale parameter

``` yml
# app/parameters.yml
parameters:
    # ...
    locale: en_US # Or "de_DE"; "fr_FR" ...
    # ...

```

### Mapping

``` yml
# app/config/config.yml
doctrine:
    dbal:
    # your dbal config here

    orm:
        auto_generate_proxy_classes: %kernel.debug%
        auto_mapping: true
        # only these lines are added additionally 
        mappings:
            translatable:
                type: annotation
                alias: Gedmo
                prefix: Gedmo\Translatable\Entity
                # make sure vendor library location is correct
                dir: "%kernel.root_dir%/../vendor/gedmo/doctrine-extensions/lib/Gedmo/Translatable/Entity"
```

After that, running **php app/console doctrine:mapping:info**

For more details, review the [Gedmo](https://github.com/Atlantic18/DoctrineExtensions/blob/master/doc/symfony2.md) doc

### Doctrine extension listener services

``` yml
# app/config/config.yml
services:
    extension.listener:
            class: Prims47\Bundle\TranslateBundle\Listener\DoctrineExtensionListener
            calls:
                - [ setContainer, [ @service_container ] ]
            tags:
                # translatable sets locale after router processing
                - { name: kernel.event_listener, event: kernel.request, method: onLateKernelRequest, priority: -10 }
                # loggable hooks user username if one is in security context
                - { name: kernel.event_listener, event: kernel.request, method: onKernelRequest }

    # Doctrine Extension listeners to handle behaviors
    gedmo.listener.translatable:
        class: Gedmo\Translatable\TranslatableListener
        tags:
            - { name: doctrine.event_subscriber, connection: default }
        calls:
            - [ setAnnotationReader, [ @annotation_reader ] ]
            - [ setDefaultLocale, [ en ] ]
            - [ setTranslationFallback, [ true ] ]
```

### Translatable Entity example

``` php
<?php

namespace Acme\DemoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * Post
 */
class Post implements Translatable
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @Gedmo\Locale
     */
    private $locale;

    /**
     * @Gedmo\Translatable
     *
     * @var string
     */
    private $title;

    /**
     * @Gedmo\Translatable
     *
     * @var string
     */
    private $content;

    
    public function setId($id)
    {
        $this->id = $id;
    }
    
    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}
```

### Xml mapping example

``` xml
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping" xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping http://doctrine-project.org/schemas/orm/doctrine-mapping.xsd">
    <entity repository-class="Acme\DemoBundle\Repository\PostRepository" name="Acme\DemoBundle\Entity\Post">
        
        <!-- Don't forget xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping" -->
        
        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>

        <field name="title"   column="title"   type="string" length="255">
            <gedmo:translatable/>
        </field>
        <field name="content" column="content" type="text">
            <gedmo:translatable/>
        </field>

        <gedmo:translation entity="Gedmo\Translatable\Entity\Translation" locale="locale"/>

    </entity>
</doctrine-mapping>
```

### Basic usage examples

Currently a global locale used for translations is in your parameters.yml (en_US for this exemple)

Use Doctrine Manager
