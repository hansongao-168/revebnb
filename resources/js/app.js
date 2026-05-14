import Alpine from 'alpinejs';
import { registerStayBookingData } from './site-booking-calendar.js';

registerStayBookingData(Alpine);

window.Alpine = Alpine;
Alpine.start();
