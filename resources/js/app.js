import './bootstrap';
import Alpine from 'alpinejs';
import 'driver.js/dist/driver.css';
import { startMemberTour, isTourCompleted, resetTour } from './tour.js';

window.Alpine = Alpine;
window.AbiyTsomStartTour = startMemberTour;
window.AbiyTsomIsTourCompleted = isTourCompleted;
window.AbiyTsomResetTour = resetTour;
Alpine.start();