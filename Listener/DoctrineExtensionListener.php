<?php
/*
 * This file is part of Prims47.
 *
 * (c) Ilan B <ilan.prims@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prims47\Bundle\TranslateBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DoctrineExtensionListener implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function onLateKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($request->getSession()->has('_locale')) {
            $locale = $request->getSession()->get('_locale');
        }else {
            $locale = $request->getLocale();
        }

        $translatable = $this->container->get('gedmo.listener.translatable');
        $translatable->setTranslatableLocale($locale);

        $symfonyTranslator = $this->container->get('translator');
        $symfonyTranslator->setLocale($locale);

        $request->setLocale($locale);
    }
} 