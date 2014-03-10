<?php

/**
 * artists_catalog extension for Contao Open Source CMS
 *
 * Copyright (C) 2014 Codefog
 *
 * @package artists_catalog
 * @author  Valentin Ruhry <http://ruhry.at>
 * @author  Codefog <http://codefog.pl>
 * @author  Kamil Kuzminski <kamil.kuzminski@codefog.pl>
 * @license LGPL
 */

namespace ArtistsCatalog;

/**
 * Class ModuleProjectReader
 *
 * Front end module "project reader".
 */
class ModuleProjectReader extends \Module
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_ac_project_reader';


    /**
     * Display a wildcard in the back end
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE')
        {
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['ac_project_reader'][0]) . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        // Set the item from the auto_item parameter
        if (!isset($_GET['items']) && $GLOBALS['TL_CONFIG']['useAutoItem'] && isset($_GET['auto_item']))
        {
            \Input::setGet('items', \Input::get('auto_item'));
        }

        // Do not index or cache the page if no project has been specified
        if (!\Input::get('items'))
        {
            global $objPage;
            $objPage->noSearch = 1;
            $objPage->cache = 0;
            return '';
        }

        return parent::generate();
    }


    /**
     * Generate the module
     */
    protected function compile()
    {
        global $objPage;
        $objProject = ProjectModel::findByAliasAndPage(\Input::get('items'), $objPage->id);

        // Do not index or cache the page if no project was found
        if ($objProject === null)
        {
            $objPage->noSearch = 1;
            $objPage->cache = 0;
            return;
        }

        $this->Template->setData($objProject->row());
        $this->Template->description = \String::toHtml5($objProject->description);
        $this->Template->lightbox = $this->fullsize;
        $objImages = ProjectImageModel::findByPid($objProject->id);

        // Add images
        if ($objImages !== null)
        {
            $imgSize = deserialize($this->imgSize, true);
            $arrImages = array();

            while ($objImages->next())
            {
                $arrImages[$objImages->id] = $objImages->row();
                $arrImages[$objImages->id]['linkTitle'] = specialchars($objImages->headline);

                // Add the image
                if (($objFile = \FilesModel::findByPk($objImages->singleSRC)) !== null && is_file(TL_ROOT . '/' . $objFile->path))
                {
                    $arrImages[$objImages->id]['src'] = $objFile->path;
                    $arrImages[$objImages->id]['lightbox'] = $objImages->id;
                    $arrImages[$objImages->id]['singleSRC'] = \Image::getHtml(\Image::get($objFile->path, $imgSize[0], $imgSize[1], $imgSize[2]), $objImages->headline);
                }
            }

            $this->Template->images = $arrImages;
        }

        // Overwrite the page title and description
        $objPage->pageTitle = strip_tags(strip_insert_tags($objProject->name));
        $objPage->description = $this->prepareMetaDescription($objProject->description);
    }
}

