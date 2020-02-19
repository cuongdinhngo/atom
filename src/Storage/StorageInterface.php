<?php

namespace Atom\Storage;

interface StorageInterface
{
	public function upload(string $directory, string $file);
	public function remove(string $fileName);
}
