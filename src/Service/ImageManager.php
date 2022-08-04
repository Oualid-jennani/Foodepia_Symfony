<?php
namespace App\Service;

use App\Entity\Menu;
use App\Entity\SubMenu;
use App\Entity\User;
use App\Form\Model\AssertGallery;
use App\Form\Model\AssertMenuImage;
use Doctrine\ORM\EntityManagerInterface;
use mysql_xdevapi\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class ImageManager
{
    /**
     * @var Security
     */
    private $security;

    /**
     * @var User
     */
    private $user;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var array
     */
    private $newImages;

    /**
     * @var FlashBagInterface
     */
    private $flash;


    /**
     * Service Images Constructor.
     * @param Security $security
     * @param EntityManagerInterface $manager
     * @param ValidatorInterface $validator
     * @param FlashBagInterface $flash
     */
    public function __construct(
        Security $security,
        EntityManagerInterface $manager,
        ValidatorInterface $validator,
        FlashBagInterface $flash
    ) {
        $this->security = $security;
        $this->user = $this->security->getUser();
        $this->manager = $manager;
        $this->validator = $validator;
        $this->flash = $flash;
        $this->newImages = [];
        $this->fileSystem = new Filesystem();
    }

    //<editor-fold desc="Code Profile">
    public function updateProfile($profileImage)
    {
        if ($profileImage) {
            $oldFileName = $this->user->getProfileImage();
            $url = "images/profile_image";
            $newProfileImageFilename = uniqid().'.'.$profileImage->guessExtension();
            try {
                $profileImage->move($url,$newProfileImageFilename);
                if(null != $oldFileName && true === $this->fileSystem->exists($url."/".$oldFileName))
                {
                    $this->fileSystem->remove([$url."/".$oldFileName]);
                }
                $this->user->setProfileImage($newProfileImageFilename);
                $this->manager->persist($this->user);
                $this->manager->flush();

                $this->flash->add('success','Your Profile Image has been changed successfully');

            } catch (\Exception $ex) {
                $this->flash->add('error','Error Profile Img');
            }
        }
    }
    //</editor-fold>

    //<editor-fold desc="Code Cin Image">
    public function uploadCinImage($profileCin,User $partner)
    {
        if ($profileCin) {
            $oldFileName = $partner->getProfileImage();
            $url = "images/cin_image";
            $newCinImageFilename = uniqid().'.'.$profileCin->guessExtension();
            try {
                $profileCin->move($url,$newCinImageFilename);
                if(null != $oldFileName && true === $this->fileSystem->exists($url."/".$oldFileName))
                {
                    $this->fileSystem->remove([$url."/".$oldFileName]);
                }
                $partner->setCinImage($newCinImageFilename);
                $this->manager->persist($partner);
                $this->manager->flush();

                $this->flash->add('success','The Cin Image has been Added successfully');

            } catch (\Exception $ex) {
                $this->flash->add('error','Error Cin Image');
            }
        }
    }
    //</editor-fold>

    //<editor-fold desc="Code Cover">
    public function updateCover($coverImage)
    {
        if ($coverImage) {
            $oldFileName = $this->user->getCoverImage();
            $url = "images/cover_image";
            $newCoverImageFilename = uniqid().'.'.$coverImage->guessExtension();
            try {
                $coverImage->move($url,$newCoverImageFilename);
                if(null != $oldFileName && true === $this->fileSystem->exists($url."/".$oldFileName))
                {
                    $this->fileSystem->remove([$url."/".$oldFileName]);
                }
                $this->user->setCoverImage($newCoverImageFilename);
                $this->manager->persist($this->user);
                $this->manager->flush();

                $this->flash->add('success','Your Cover Image has been changed successfully');
            } catch (\Exception $ex) {
                $this->flash->add('error','Error Cover Img');
            }
        }
    }
    //</editor-fold>

    //<editor-fold desc="Code Gallery">
    public function updateGallery(Request $request,User $user)
    {
        $url = 'images/gallery_partner/gallery'.$this->user->getId();
        $oldImagesRequest = (array)$request->request->get("oldPic");
        $oldImagesUser = $this->user->getGallery()->getImages();
        $newImagesRequest = $request->files->get('pic');

        $this->createFolderUrl($url);
        $this->removeOldImages($oldImagesUser,$oldImagesRequest,$url);
        $this->saveImages($newImagesRequest,new AssertGallery(),$url);

        $user->getGallery()->setImages($this->newImages);
    }
    //</editor-fold>

    //<editor-fold desc="Code Image Menu">
    public function addMenuImages($newImages,Menu $menu)
    {
        $url = 'images/menu';
        if($newImages){
            /** @var UploadedFile $pic */
            foreach($newImages as $pic){
                if(!$this->saveImage($pic,$url)){break;}
            }
            $menu->setImages($this->newImages);
        }
    }

    public function updateMenuImages(Request $request,Menu $menu)
    {
        $url = 'images/menu';
        $oldImagesRequest = (array)$request->request->get("oldPic");
        $oldImagesProduct = $menu->getImages();
        $newImagesRequest = $request->files->get('pic');

        $this->createFolderUrl($url);
        $this->removeOldImages($oldImagesProduct,$oldImagesRequest,$url);
        $this->saveImages($newImagesRequest,new AssertMenuImage(),$url);

        $menu->setImages($this->newImages);
    }

    public function deleteMenuImages(Menu $menu)
    {
        $oldFileName = $menu->getImages();
        foreach ($oldFileName as $src ){
            if($this->fileSystem->exists('images/menu/'.$src)){
                $this->fileSystem->remove(['images/menu/'.$src]);
            }
        }
    }
    //</editor-fold>

    //<editor-fold desc="Code Image sub Menu">
    public function addSubMenuImage($brochureFile,SubMenu $subMenu)
    {
        if ($brochureFile) {
            $url = 'images/sub_menu';
            $this->createFolderUrl($url);
            $newFilename = uniqid().'.'.$brochureFile->guessExtension();
            try {
                $brochureFile->move($url,$newFilename);
            } catch (Exception $ex){
                $this->flash->add('error','error');
            }
            $subMenu->setImageUrl($newFilename);
        }
    }

    public function updateSubMenuImage($brochureFile,SubMenu $subMenu)
    {
        if ($brochureFile) {
            $url = 'images/sub_menu';
            $this->createFolderUrl($url);

            $oldFileName = $subMenu->getImageUrl();
            $newFilename = uniqid().'.'.$brochureFile->guessExtension();

            $brochureFile->move($url,$newFilename);

            if(true === $this->fileSystem->exists("images/sub_menu/".$oldFileName))
            {
                $this->fileSystem->remove(['images/sub_menu/'.$oldFileName]);
            }
            $subMenu->setImageUrl($newFilename);
        }
    }

    public function deleteSubMenuImage(SubMenu $subMenu)
    {
        $oldFileName = $subMenu->getImageUrl();
        if(true === $this->fileSystem->exists("images/sub_menu/".$oldFileName))
        {
            $this->fileSystem->remove(['images/sub_menu/'.$oldFileName]);
        }
    }

    //</editor-fold>



    //<editor-fold desc="Code Other functions file">
    public function createFolderUrl($url)
    {
        if(false === $this->fileSystem->exists($url))
        {
            $this->fileSystem->mkdir($url);
        }
    }

    public function removeOldImages($currantImages,$imagesRequest,$url)
    {
        foreach($currantImages as $src){
            if (!\in_array($src, $imagesRequest)) {
                $this->fileSystem->remove([$url.'/'.$src]);
            } else {
                if(count($this->newImages) < 5){
                    $this->newImages[] = $src;
                }
            }
        }
    }

    public function saveImages($pictures,$assertImage,$url)
    {
        $countFlashImage = 0;
        /** @var UploadedFile $pic */
        foreach($pictures as $pic){
            $assertImage->setBrochure($pic);
            /** @var ConstraintViolationList $errors */
            $errors = $this->validator->validate($assertImage);
            if (count($errors) > 0) {
                $countFlashImage++;
                foreach ($errors as $er){
                    $this->flash->add('errorImage', $er->getMessage());
                }
            } else {
                if(!$this->saveImage($pic,$url)){break;}
            }
        }
        $this->flash->add('countFlashImage', $countFlashImage);
    }

    public function saveImage($pic,$url)
    {
        if('' !== $path = $pic->getClientOriginalName()){
            $ext = \pathinfo($path, PATHINFO_EXTENSION);
            $src = \uniqid().'.'.$ext;
            $pic->move($url, $src);
            if(count($this->newImages) < 5){
                $this->newImages[] = $src;
                return true;
            }else{return false;}
        }
    }

    //</editor-fold>

}