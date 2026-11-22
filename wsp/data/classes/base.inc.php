<?php
/**
 * SQL access for WSP.
 *
 * This class wraps the active mysqli connection and keeps the result format
 * used by the existing doSQL() function.
 */
if (!class_exists('wspBase')) {
    class wspBase {

        public const VERSION = '6.26.11';

        public string $lockstat = '';
        public int $mgroup = 0;
        public string $fpos = '';
        public bool $fposcheck = false;
        public bool $preventleave = true;
        public string $updatekey = 'ahfsa9r278rtSNDKJaou387zrfsdfchizqrw';
        public string $updateuri = 'https://update.wsp-server.info/wsp';
        public string $updatefiles = 'https://update.wsp-server.info/wsp';
        public string $updatedatabase = 'https://update.wsp-server.info/updater';

        public function setPage(
            string $lockstat = '', ?int $mgroup = null, 
            string $fpos = '', bool $fposcheck = false, 
            bool $preventleave = true): void
        {
            $this->lockstat = $lockstat;
            $this->mgroup = $mgroup;
            $this->fpos = $fpos;
            $this->fposcheck = $fposcheck;
            $this->preventleave = $preventleave;
        }

        public function getPage(?string $param = null): mixed
        {
            $data = [
                'lockstat' => $this->lockstat,
                'mgroup' => $this->mgroup,
                'fpos' => $this->fpos,
                'fposcheck' => $this->fposcheck,
                'preventleave' => $this->preventleave,
            ];
            return (!empty($param) && isset($data[$param])) ? $data[$param] : $data;
        }

        /**
         * rechtemoeglichkeiten, die die rechtebereiche annehmen koennen
         * yes|no|auswahl, mit yes = 1, no = 0 und auswahl = 2
         */
        public function getRightpossibilities(): array
        {
           return [1, 1, 2, 1, 2, 1, 2];
        }

        public function getRightabilities(): array
        {
            /*
            return [
                "siteprops", // einstellungen zu meta-tags etc
                "sitestructure", // menuestruktur
                "contents", // inhalte
                "rss",
                "publisher", // publisher
                "design", // design
                "menufacts", // menuestruktur
                "imagesfolder",
                "downloadfolder",
                "flashfolder",
                "videofolder",
                "fontsfolder",
            ]
            */
            return [
                "siteprops" => 1,
                'sitestructure' => 1,
                'contents' => 2,
                'rss' => 0, // <= deprecated
                'publisher' => 2,
                'design' => 1,
                'menufacts' => 2,
                'imagesfolder' => null,
                "downloadfolder" => null,
                "flashfolder" => null,
                "videofolder" => null,
                "fontsfolder" => null,
            ];
        }

        public function getUpdater(?string $param = null): mixed
        {
            $data = [
                'updatekey' => $this->updatekey,
                'updateuri' => $this->updateuri,
                'updatefiles' => $this->updatefiles,
                'updatedatabase' => $this->updatedatabase,
            ];
            return (!empty($param) && isset($data[$param])) ? $data[$param] : $data;
        }

        /**
         * returns directories to use on install modules process
         */
        public function getAllowdir(): array
        {
            return [
                'data/menu',
                'data/modules',
                'media/fonts',
                'media/video',
                'media/images',
                'media/screen',
                'media/layout',
                'media/download',
                '[wsp]/data/interpreter',
                '[wsp]/data/lang',
                '[wsp]/data/modsetup',
                '[wsp]/data/modules',
                '[wsp]/media/javascript',
                '[wsp]/media/screen'
            ];
        }

        /**
         * ToDo: System tables should be fetched more dynamically
         */
        public function getTables(): array
        {
            return [
                'content',
                'content_backup',
                'content_parsefile',
                'globalcontent',
                'interpreter',
                'javascript',
                'mediadesc',
                'menu',
                'modules',
                'options',
                'pageproperties',
                'restrictions',
                'rssdata',
                'rssentries',
                'r_temp_jscript',
                'r_temp_rss',
                'r_temp_styles',
                'security',
                'securitylog',
                'selfvars',
                'siteproperties',
                'site_tracking',
                'stylesheets',
                'templates',
                'templates_menu',
                'tracking_files',
                'tracking_info',
                'usercontrol',
                'wspaccess',
                'wspmedia',
                'wspmenu',
                'wspmsg',
                'wspplugins',
                'wspproperties',
                'wspqueue',
                'wsprights'
            ];
        }

        public function getForbiddenTables(): array
        {
            /* ToDo: System tables should be fetched more dynamically */
            return array_merge($this->getTables(), ['reference']);
        }

    }
}
