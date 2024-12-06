<?php declare(strict_types = 1);

namespace Nettrine\ORM\DI\Helpers;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Definition;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nettrine\ORM\DI\OrmExtension;
use Nettrine\ORM\Exception\LogicalException;

class MappingHelper
{

	private CompilerExtension $extension;

	private function __construct(CompilerExtension $extension)
	{
		$this->extension = $extension;
	}

	public static function of(CompilerExtension $extension): self
	{
		return new self($extension);
	}

	public function addAttribute(string $namespace, string $path): self
	{
		if (!is_dir($path)) {
			throw new LogicalException(sprintf('Given mapping path "%s" does not exist', $path));
		}

		/** @var ServiceDefinition $attributeDriver */
		$attributeDriver = $this->getService(OrmExtension::MANAGER_TAG, 'AttributeDriver');
		$attributeDriver->addSetup('addPaths', [[$path]]);

		/** @var ServiceDefinition $chainDriver */
		$chainDriver = $this->getService(OrmExtension::MAPPING_DRIVER_TAG, 'MappingDriverChain');
		$chainDriver->addSetup('addDriver', [$attributeDriver, $namespace]);

		return $this;
	}

	public function addXml(string $namespace, string $path, bool $simple = false): self
	{
		if (!is_dir($path)) {
			throw new LogicalException(sprintf('Given mapping path "%s" does not exist', $path));
		}

		/** @var ServiceDefinition $xmlDriver */
		$xmlDriver = $this->getService(OrmExtension::MANAGER_TAG, 'XmlDriver');

		if ($simple) {
			$xmlDriver->addSetup(new Statement('$service->getLocator()->addNamespacePrefixes([? => ?])', [$path, $namespace]));
		} else {
			$xmlDriver->addSetup(new Statement('$service->getLocator()->addPaths([?])', [$path]));
		}

		/** @var ServiceDefinition $chainDriver */
		$chainDriver = $this->getService(OrmExtension::MAPPING_DRIVER_TAG, 'MappingDriverChain');
		$chainDriver->addSetup('addDriver', [$xmlDriver, $namespace]);

		return $this;
	}

	private function getService(string $tag, string $name): Definition
	{
		$builder = $this->extension->getContainerBuilder();

		$service = $builder->findByTag($tag);

		if ($service === []) {
			throw new LogicalException(sprintf('Service "%s" not found by tag "%s"', $name, $tag));
		}

		return $builder->getDefinition(current(array_keys($service)));
	}

}
