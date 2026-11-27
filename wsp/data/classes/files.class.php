<?php

class wspFiles
{
    public const DEFAULT_EXCLUDED_FOLDERS = ['thumbs', 'preview'];

    /**
     * List the immediate entries in a folder beneath the installation's /media.
     * File sizes are in bytes. Folder sizes count only direct files.
     * Modification times are Unix timestamps in seconds.
     * The type is the alphabetical folder ID, or the last folder ID plus two for files.
     * Pass folder or files to return only that branch.
     * A non-empty allow list takes precedence over the exclude list.
     *
     * @param string[] $allowedExtensions
     * @param string[] $excludedExtensions
     * @param string[] $excludedFolders Folder names to omit at any level.
     */
    public function listFolder(
        string $folder = '/media/images/',
        ?string $branch = null,
        array $allowedExtensions = [],
        array $excludedExtensions = [],
        array $excludedFolders = self::DEFAULT_EXCLUDED_FOLDERS
    ): string {
        if ($branch !== null && !in_array($branch, ['folder', 'files'], true)) {
            return '{"error":"invalid_media_branch"}';
        }

        $folder = str_replace('\\', '/', $folder);
        $folder = rtrim($folder, '/');
        if (($folder !== '/media' && !str_starts_with($folder, '/media/'))
            || str_contains($folder, "\0") || str_contains($folder, ':')
            || in_array('..', explode('/', $folder), true)) {
            return '{"error":"invalid_media_folder"}';
        }

        $excludedFolders = array_map('strtolower', $excludedFolders);
        if (array_intersect(explode('/', strtolower(substr($folder, strlen('/media')))), $excludedFolders)) {
            return '{"error":"media_folder_excluded"}';
        }

        // __DIR__ is <project root>/wsp/data/classes.
        $projectRoot = dirname(__DIR__, 3);
        $mediaRoot = realpath($projectRoot . '/media');
        $directory = realpath($projectRoot . $folder);
        if ($mediaRoot === false || $directory === false
            || !$this->isInsideMedia($directory, $mediaRoot) || !is_dir($directory)) {
            return '{"error":"media_folder_not_found"}';
        }

        $names = @scandir($directory);
        if ($names === false) {
            return '{"error":"media_folder_unreadable"}';
        }
        usort($names, 'strcasecmp');

        $useAllowList = $allowedExtensions !== [];
        $extensions = array_map(
            static fn(string $extension): string => strtolower(ltrim(trim($extension), '.')),
            $useAllowList ? $allowedExtensions : $excludedExtensions
        );

        $entries = $branch === null ? ['folder' => [], 'files' => []] : [$branch => []];
        $folderId = 0;
        foreach ($names as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $name;
            $resolvedPath = realpath($path);
            if ($resolvedPath === false || is_link($path)
                || !$this->isInsideMedia($resolvedPath, $mediaRoot)) {
                continue;
            }

            $isFolder = is_dir($resolvedPath);
            if (!$isFolder && !is_file($resolvedPath)) {
                continue;
            }

            $entryBranch = $isFolder ? 'folder' : 'files';
            if ($isFolder) {
                if (in_array(strtolower($name), $excludedFolders, true)) {
                    continue;
                }
                $folderId++;
            }
            if (!isset($entries[$entryBranch])) {
                continue;
            }

            if (!$isFolder && $extensions !== []) {
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($extension, $extensions, true) !== $useAllowList) {
                    continue;
                }
            }

            $size = $isFolder ? $this->countFiles($resolvedPath) : @filesize($resolvedPath);
            $modified = @filemtime($resolvedPath);
            $entries[$entryBranch][] = [
                'name' => $name,
                'path' => $resolvedPath,
                'type' => $isFolder ? $folderId : null,
                'size' => $size === false ? null : $size,
                'modified' => $modified === false ? null : $modified,
            ];
        }

        foreach (array_keys($entries['files'] ?? []) as $index) {
            $entries['files'][$index]['type'] = $folderId + 2;
        }

        return json_encode($entries, JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
    }

    private function isInsideMedia(string $path, string $mediaRoot): bool
    {
        return $path === $mediaRoot || str_starts_with($path, $mediaRoot . DIRECTORY_SEPARATOR);
    }

    private function countFiles(string $directory): ?int
    {
        $names = @scandir($directory);
        if ($names === false) {
            return null;
        }

        $count = 0;
        foreach ($names as $name) {
            $path = $directory . DIRECTORY_SEPARATOR . $name;
            if (is_file($path) && !is_link($path)) {
                $count++;
            }
        }

        return $count;
    }
}
