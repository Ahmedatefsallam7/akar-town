// FullCalendar for the dashboard's meetings calendar, imported by the widget's Alpine component (only there).
import { Calendar } from '@fullcalendar/core'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import arLocale from '@fullcalendar/core/locales/ar'

window.MeetingsCalendar = { Calendar, plugins: [dayGridPlugin, timeGridPlugin], locales: [arLocale] }
