import Alpine from 'alpinejs';
import { registerGuestPicker } from './guest-picker.js';
import { registerMobileSearch } from './mobile-search.js';
import { registerStayBookingData } from './site-booking-calendar.js';

registerGuestPicker(Alpine);
registerMobileSearch(Alpine);
registerStayBookingData(Alpine);

window.Alpine = Alpine;
Alpine.start();
