<?php

declare(strict_types=1);

namespace Williarin\Cook;

interface StateInterface
{
    public function getCurrentPackage(): ?string;
    public function setCurrentPackage(?string $currentPackage): self;
    public function getOverwrite(): bool;
    public function setOverwrite(bool $overwrite): self;
    public function getProjectDirectory(): string;
    public function getVendorDirectory(): string;
    public function getCurrentPackageDirectory(): string;
    public function getCurrentPackageRecipePathname(): ?string;
    public function replacePathPlaceholders(string $pathname): string;
}
