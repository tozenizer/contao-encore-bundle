<?php

namespace HeimrichHannot\EncoreBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\LayoutModel;
use Contao\PageModel;
use HeimrichHannot\EncoreBundle\Asset\TemplateAsset;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Hook("parseBackendTemplate")
 */
class ParseBackendTemplateListener {
    protected ContainerInterface $container;
    protected TemplateAsset $templateAsset;

    public function __construct(ContainerInterface $container, TemplateAsset $templateAsset) {
        $this->container = $container;
        $this->templateAsset = $templateAsset;

        if ($this->container->has('HeimrichHannot\EncoreBundle\Asset\FrontendAsset')) {
            $this->container->get(\HeimrichHannot\EncoreBundle\Asset\FrontendAsset::class)->addActiveEntrypoint('backend');
        }
    }

    public function __invoke(string $buffer, string $template): string {
        if ('be_main' === $template) {
            $templateAssets = $this->templateAsset->createInstance(new PageModel(), new LayoutModel(), 'encoreEntries');

            $GLOBALS['TL_BE_HEAD'][] = trim($templateAssets->linkTags());
            $GLOBALS['TL_BE_HEAD'][] = trim($templateAssets->headScriptTags());
            
            // TODO: Move to body output maybe?
            $GLOBALS['TL_BE_HEAD'][] = trim($templateAssets->scriptTags());
        }

        return $buffer;
    }
}