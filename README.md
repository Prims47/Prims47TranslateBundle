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
    <entity repository-class="Acme\DemoBundle\Entity\PostRepository" name="Acme\DemoBundle\Entity\Post">
        
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
After that, running **php app/console doctrine:schema:update --force**

### Basic usage examples

Currently a global locale used for translations in your parameters.yml (en_US for this exemple) which was set in DoctrineExtensionListener.

#### Step 0: Create your manager

Before start the translation, we must create a manager:

``` php
<?php

namespace Acme\DemoBundle\Entity;

use Prims47\Bundle\TranslateBundle\Doctrine\Common\BaseManager;

class PostManager extends BaseManager
{
    /**
     * Find all post by locale.
     *
     * @return array
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }
    
    /**
     * Find Post by id.
     *
     * @param integer $id
     *
     * @return mixed|null|object
     */
    public function find($id)
    {
        return $this->getRepository()->find($id);
    }
}
```

After this, you must declare this manager has a service.

``` xml
<!-- Acme/DemoBundle/Resources/config/services.xml -->
<?xml version="1.0" ?>

<container xmlns="http://symfony.com/schema/dic/services"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

    <services>
        <service id="acme.demo.post.manager" class="Acme\DemoBundle\Entity\PostManager">
            <argument type="service" id="doctrine.orm.default_entity_manager" />
            <argument>Acme\DemoBundle\Entity\Post</argument> <!-- Your entity -->
            <call method="setRepositoryLocale">
                <argument type="service" id="service_container" /> <!-- You must call this method for declare your locale dynamically -->
            </call>
        </service>
    </services>
</container>
```

#### Step 1: Adapt your repository
``` php
<?php

namespace Acme\DemoBundle\Entity;

use Prims47\Bundle\TranslateBundle\Doctrine\ORM\EntityRepository;

/**
 * PostRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PostRepository extends EntityRepository
{
    /**
     * Find all post by locale.
     *
     * @return array
     */
    public function findAll()
    {
        $qb = $this->createQueryBuilder('p');

        return $this->getResult($qb);
    }
    
    /**
     * Find Post by id.
     *
     * @param integer $id
     *
     * @return mixed|null|object
     */
    public function find($id)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.id = :id');
        $qb->setParameter('id', $id);

        return $this->getOneOrNullResult($qb);
    }
}
```

#### Step 1: Basic usage
To save post with its translations:

``` php
<?php

/** @var PostManager $postManager */
$postManager = $this->get('acme.demo.post.manager');

$post = new Post();
$post->setTitle('My first title in english');
$post->setContent('My first content in english');

$postManager->save($post, true);

$post = $postManager->find(1);
```
Lets update our post in different locale:

``` php
<?php
/** @var PostManager $postManager */
$postManager = $this->get('acme.demo.post.manager');

$post = $postManager->find(1);

$post->setTitle('Mon premier titre en français');
$post->setContent('Mon premier contenu en français');
$post->setTranslatableLocale('fr_FR');

$postManager->save($post, true);

```

Now change your local parameter:

They are two methods.

* Change your local in parameters.yml
* Or add in session the local value :

``` php
<?php

$session = $this->get('session');
$session->set('locale', 'fr_FR');

/** @var PostManager $postManager */
$postManager = $this->get('acme.demo.post.manager');

$post = $postManager->find(1);

```
