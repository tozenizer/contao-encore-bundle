# Developers

This document contains additional information for developers working with encore bundle.

## Add entries from your code (frontend module, content element,...)

Since version 1.3 it is possible to add encore entries from your code. So for example the slider assets are automatically included, if the slider module is added to the page. To do this, you can use the `FrontendAsset` service.

Example with ServiceSubscriber (recommended):

```php
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceSubscriberInterface;

class AcmeController implements ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function addEncoreAssets()
    {

        if ($this->container->has('HeimrichHannot\EncoreBundle\Asset\FrontendAsset')) {
            $this->container->get(\HeimrichHannot\EncoreBundle\Asset\FrontendAsset::class)->addActiveEntrypoint('contao-acme-bundle');
        }
    }

    public static function getSubscribedServices()
    {
        return [
            '?HeimrichHannot\EncoreBundle\Asset\FrontendAsset'
        ];
    }
}
```

Example with optional dependency injection: 

```yaml
# services.yml
App/FrontendModule/MyModule:
    calls:
      - [setEncoreFrontendAsset, ['@?HeimrichHannot\EncoreBundle\Asset\FrontendAsset']]
```

```php
class MyModule
{
    protected $encoreFrontendAsset;

    public function setEncoreFrontendAsset(\HeimrichHannot\EncoreBundle\Asset\FrontendAsset $encoreFrontendAsset): void {
        $this->encoreFrontendAsset = $encoreFrontendAsset;
    }

    public function getResponse() {
        // ...
        if ($this->encoreFrontendAsset) {
            $this->encoreFrontendAsset->addActiveEntrypoint('mymodule-assets');
        }
        //...
    }
}
```

Example for legacy code (old frontend modules or content elements): 

```php
if (\Contao\System::getContainer()->has('huh.encore.asset.frontend')) {
    \Contao\System::getContainer()->get('huh.encore.asset.frontend')->addActiveEntrypoint('contao-slick-bundle');
}
```

## Make encore bundle an optional dependency

If you create a reusable bundle and want to support setups that don't use encore, you need adjust the encore bundle configuration:

1. Move your `huh_encore` configuration to an own config file, for example `config_encore.yml`.

1. In your `Plugin` class implement the `ConfigPluginInterface` and load the config, if encore bundle is installed. 

    ```php
    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig)
    {
        if (class_exists('HeimrichHannot\EncoreBundle\HeimrichHannotContaoEncoreBundle')) {
            $loader->load("@HeimrichHannotVideoBundle/Resources/config/config_encore.yml");
        }
    }
    ```

1. Optional: Add encore bundle to your composer.json suggest section.

    ```json
    "suggest": {
        "heimrichhannot/contao-encore-bundle": "Symfony Webpack Encore integration for Contao.",
      }
    ``` 

## Add encore entries to custom template

If you don't want to render assets on page basis, it is possible to generate a custom set of encore entries.

1. Create an `EntrypointCollection` with the `EntrypointCollectionFactory` service
1. Get your assets with `TemplateAssetGenerator` service. 
1. Optional: If you want an input field in the contao backend to select entries, you can use the `DcaGenerator` service to generate an input like on layout or page settings.

```php
use Contao\FrontendTemplate;
use HeimrichHannot\EncoreBundle\Asset\EntrypointCollectionFactory;
use HeimrichHannot\EncoreBundle\Asset\TemplateAssetGenerator;

function renderTemplateWithEncore(array $entrypoints, EntrypointCollectionFactory $entrypointCollectionFactory, TemplateAssetGenerator $templateAssetGenerator)
{
    $template = new FrontendTemplate();
    $collection = $entrypointCollectionFactory->createCollection($entrypoints);
    $template->stylesheets = $templateAssetGenerator->linkTags($collection);
    $template->headJavaScript = $templateAssetGenerator->headScriptTags($collection);
    $template->javaScript = $templateAssetGenerator->scriptTags($collection);
    return $template->getResponse();
}
```

It is also possible to get the stylesheets inline:

```php
use Contao\FrontendTemplate;
use HeimrichHannot\EncoreBundle\Asset\EntrypointCollectionFactory;
use HeimrichHannot\EncoreBundle\Asset\TemplateAssetGenerator;

function renderTemplateWithEncore(array $entrypoints, EntrypointCollectionFactory $entrypointCollectionFactory, TemplateAssetGenerator $templateAssetGenerator)
{
    $template = new FrontendTemplate();
    $collection = $entrypointCollectionFactory->createCollection($entrypoints);
    $template->inlineCss = $templateAssetGenerator->inlineCssLinkTag($collection);
    return $template->getResponse();
}
```

## ConfigurationHelper

The `ConfigurationHelper` service can be used to obtain some configuration informations. Following methods are available:

`isEnabledOnCurrentPage(?PageModel $pageModel = null): bool` - Return if encore is enabled for the current frontend page. You can pass a page object to check for a custom page, otherweise `global $objPage` is used.

`getRelativeOutputPath(): string` - Return the relative output path configured by webpack encore bundle. Typical this is `build`.

`getAbsoluteOutputPath(): string` - Return the absolute output path configured by webpack encore bundle. For example `/var/www/html/project/web/build`

## Custom import templates

If you need custom templates for the import of javascript and stylesheet assets files, Encore Bundle provide support for this. 
Create a twig template (see `src/Resources/views` for examples) and register them in your (project) bundle config.

Example:

```yaml
huh_encore:
  templates:
      imports:
      - { name: default_css, template: "@HeimrichHannotContaoEncore/encore_css_imports.html.twig" }
      - { name: default_js, template: "@HeimrichHannotContaoEncore/encore_js_imports.html.twig" }
```