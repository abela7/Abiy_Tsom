import './bootstrap';
import Alpine from 'alpinejs';
import 'driver.js/dist/driver.css';
import { startMemberTour, isTourCompleted, resetTour, syncTourCompletion } from './tour.js';

window.Alpine = Alpine;
window.AbiyTsomStartTour = startMemberTour;
window.AbiyTsomIsTourCompleted = isTourCompleted;
window.AbiyTsomResetTour = resetTour;
Alpine.start();

// Backfill: sync localStorage completion to DB for pre-existing members
syncTourCompletion();