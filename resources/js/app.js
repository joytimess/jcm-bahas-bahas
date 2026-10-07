import Alpine from 'alpinejs';
import registerImageCropper from './image-cropper';
import { registerDiscovery } from './discovery';

window.Alpine = Alpine;

registerImageCropper(Alpine);
registerDiscovery();

Alpine.start();
