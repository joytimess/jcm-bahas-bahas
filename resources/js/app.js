import Alpine from 'alpinejs';
import registerImageCropper from './image-cropper';

window.Alpine = Alpine;

registerImageCropper(Alpine);

Alpine.start();
