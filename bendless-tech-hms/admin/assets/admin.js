(function () {
  'use strict';

  let billingCycle = 'yearly';
  const roomFilters = {
    type: 'all',
    status: 'all'
  };

  function adminConfig() {
    return window.hrmAdmin || {};
  }

  function ajaxUrl() {
    return adminConfig().ajaxUrl || '';
  }

  function nonce() {
    return adminConfig().nonce || '';
  }

  function i18n(key, fallback) {
    const messages = adminConfig().i18n || {};
    return messages[key] || fallback;
  }

  function request(action, data) {
    const body = data instanceof FormData ? data : new FormData();
    body.set('action', action);
    if (!body.get('nonce')) {
      body.set('nonce', nonce());
    }

    return fetch(ajaxUrl(), {
      method: 'POST',
      credentials: 'same-origin',
      body
    }).then((response) => response.json());
  }

  window.showToast = function showToast(message, type = 'success') {
    const icons = {
      success: 'solar:check-circle-linear',
      error: 'solar:close-circle-linear',
      warning: 'solar:danger-triangle-linear',
      info: 'solar:info-circle-linear'
    };
    const colors = {
      success: 'bg-emerald-500',
      error: 'bg-red-500',
      warning: 'bg-amber-500',
      info: 'bg-blue-500'
    };
    const toast = document.createElement('div');
    toast.className = `fixed bottom-6 right-6 z-[100] flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-white shadow-modal transition-all duration-300 ${colors[type] || colors.success}`;
    toast.innerHTML = `<span class="iconify text-lg" data-icon="${icons[type] || icons.success}"></span><span>${message}</span>`;
    document.body.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(20px)';
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  };

  window.openModal = function openModal(id) {
    const overlay = document.getElementById('modal-overlay');
    const modal = document.getElementById(id);
    if (overlay) {
      overlay.classList.remove('hidden');
    }
    if (modal) {
      modal.classList.remove('hidden');
    }
  };

  window.closeModal = function closeModal(id) {
    const overlay = document.getElementById('modal-overlay');
    const modal = document.getElementById(id);
    if (modal) {
      modal.classList.add('hidden');
    }
    if (overlay) {
      overlay.classList.add('hidden');
    }
  };

  window.openSlideOver = function openSlideOver(id = 'slideover-panel') {
    const overlay = document.getElementById('slideover-overlay');
    const panel = document.getElementById(id);
    if (overlay) {
      overlay.classList.remove('hidden');
    }
    if (panel) {
      panel.classList.remove('translate-x-full');
    }
  };

  window.closeSlideOver = function closeSlideOver(id = 'slideover-panel') {
    const overlay = document.getElementById('slideover-overlay');
    const panel = document.getElementById(id);
    if (panel) {
      panel.classList.add('translate-x-full');
    }
    if (overlay) {
      overlay.classList.add('hidden');
    }
  };

  window.openUpgradeModal = function openUpgradeModal() {
    const modal = document.getElementById('hrm-upgrade-modal');
    if (modal) {
      modal.classList.remove('hidden');
      document.body.classList.add('hrm-modal-open');
    }
  };

  window.closeUpgradeModal = function closeUpgradeModal() {
    const modal = document.getElementById('hrm-upgrade-modal');
    if (modal) {
      modal.classList.add('hidden');
      document.body.classList.remove('hrm-modal-open');
    }
  };

  function updateBillingCycle(cycle) {
    billingCycle = cycle === 'yearly' ? 'yearly' : 'monthly';
    document.querySelectorAll('.hrm-billing-toggle').forEach((button) => {
      const active = button.dataset.cycle === billingCycle;
      button.classList.toggle('bg-white', active);
      button.classList.toggle('text-slate-900', active);
      button.classList.toggle('shadow-sm', active);
      button.classList.toggle('text-slate-500', !active);
    });
    document.querySelectorAll('.hrm-plan-price').forEach((node) => {
      node.textContent = billingCycle === 'yearly' ? node.dataset.yearly : node.dataset.monthly;
    });
    document.querySelectorAll('.hrm-plan-cycle').forEach((node) => {
      node.textContent = billingCycle === 'yearly' ? 'per year' : 'per month';
    });
  }

  function handleJsonResult(result, successFallback) {
    if (!result || !result.success) {
      const message = result && result.data && result.data.message ? result.data.message : i18n('actionFailed', 'The action could not be completed.');
      window.showToast(message, 'error');
      return false;
    }

    const message = result.data && result.data.message ? result.data.message : successFallback;
    if (message) {
      window.showToast(message, 'success');
    }
    if (result.data && result.data.redirect) {
      window.location.href = result.data.redirect;
    }
    return true;
  }

  function escapeHtml(value) {
    return String(value === null || value === undefined ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function statusClasses(status) {
    const map = {
      available: 'bg-emerald-50 text-emerald-700 border-emerald-100',
      occupied: 'bg-red-50 text-red-700 border-red-100',
      cleaning: 'bg-amber-50 text-amber-700 border-amber-100',
      maintenance: 'bg-slate-100 text-slate-600 border-slate-200'
    };
    return map[status] || map.maintenance;
  }

  function statusIcon(status) {
    const icons = {
      available: 'solar:check-circle-linear',
      occupied: 'solar:user-block-linear',
      cleaning: 'solar:star-rings-linear',
      maintenance: 'solar:widget-2-linear'
    };
    return icons[status] || icons.maintenance;
  }

  function renderRoomGrid(rooms) {
    const grid = document.querySelector('.hrm-room-grid-live');
    if (!grid) {
      return;
    }

    if (!rooms || !rooms.length) {
      grid.innerHTML = `<div class="col-span-full rounded-xl bg-surface-50 px-4 py-6 text-center text-sm font-medium text-slate-500">${escapeHtml(i18n('noRooms', 'No rooms have been added yet.'))}</div>`;
      return;
    }

    grid.innerHTML = rooms.map((room) => {
      const status = String(room.status || 'maintenance');
      return `
        <div class="hrm-filterable-room rounded-2xl border ${statusClasses(status)} bg-white p-5 shadow-card" data-room-type="${escapeHtml(room.room_type || '')}" data-room-status="${escapeHtml(status)}">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="truncate text-xl font-bold text-slate-900">${escapeHtml(room.room_number)}</p>
              <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">${escapeHtml(room.room_type).replace(/_/g, ' ')}</p>
            </div>
            <span class="iconify text-2xl" data-icon="${statusIcon(status)}"></span>
          </div>
          <div class="mt-4 flex items-center justify-between gap-3">
            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold ${statusClasses(status)}">
              <span class="h-1.5 w-1.5 rounded-full bg-current"></span>${escapeHtml(room.status_label || status)}
            </span>
            <span class="text-sm font-semibold text-slate-700">${escapeHtml(room.price_formatted || '')}</span>
          </div>
          <p class="mt-3 text-xs text-slate-500">Floor ${escapeHtml(room.floor)} - ${escapeHtml(room.max_guests)} guests</p>
        </div>`;
    }).join('');
    applyRoomFilters();
  }

  function updateFilterButtons(kind, value) {
    document.querySelectorAll(`.hrm-room-filter[data-filter-kind="${kind}"]`).forEach((button) => {
      const active = button.dataset.filterValue === value;
      button.classList.toggle('border-primary-500', active);
      button.classList.toggle('bg-primary-500', active);
      button.classList.toggle('text-white', active);
      button.classList.toggle('border-slate-200', !active);
      button.classList.toggle('bg-white', !active);
      button.classList.toggle('text-slate-700', !active);
      button.classList.toggle('hover:bg-surface-50', !active);
    });
  }

  function applyRoomFilters() {
    document.querySelectorAll('.hrm-filterable-room').forEach((node) => {
      const typeMatches = roomFilters.type === 'all' || node.dataset.roomType === roomFilters.type;
      const statusMatches = roomFilters.status === 'all' || node.dataset.roomStatus === roomFilters.status;
      node.classList.toggle('hidden', !(typeMatches && statusMatches));
    });
  }

  function refreshRoomGrid(silent = false) {
    const grid = document.querySelector('.hrm-room-grid-live');
    if (!grid) {
      return;
    }

    request('hrm_get_room_grid', new FormData())
      .then((result) => {
        if (!result || !result.success) {
          if (!silent) {
            handleJsonResult(result, '');
          }
          return;
        }
        renderRoomGrid(result.data.rooms || []);
        if (!silent) {
          window.showToast(i18n('saved', 'Saved successfully.'), 'success');
        }
      })
      .catch(() => {
        if (!silent) {
          window.showToast(i18n('actionFailed', 'The action could not be completed.'), 'error');
        }
      });
  }

  function resetRoomForm() {
    const form = document.querySelector('.hrm-room-form');
    if (!form) {
      return;
    }
    form.reset();
    const idField = form.querySelector('[name="room_id"]');
    if (idField) {
      idField.value = '';
    }
    const title = document.querySelector('.hrm-room-modal-title');
    if (title) {
      title.textContent = 'Add Room';
    }
  }

  function populateRoomForm(room) {
    const form = document.querySelector('.hrm-room-form');
    if (!form || !room) {
      return;
    }

    const map = {
      room_id: room.id,
      room_number: room.room_number,
      room_type: room.room_type,
      floor: room.floor,
      max_guests: room.max_guests,
      price_per_night: room.price_per_night,
      weekend_rate: room.weekend_rate,
      peak_rate: room.peak_rate,
      status: room.status,
      description: room.description,
      amenities: room.amenities,
      image_urls: room.image_urls
    };

    Object.keys(map).forEach((name) => {
      const field = form.querySelector(`[name="${name}"]`);
      if (field) {
        field.value = map[name] === null || map[name] === undefined ? '' : map[name];
      }
    });

    const title = document.querySelector('.hrm-room-modal-title');
    if (title) {
      title.textContent = `Edit Room ${room.room_number || ''}`.trim();
    }
  }

  function bookingForm() {
    return document.querySelector('.hrm-booking-form');
  }

  function resetBookingForm() {
    const form = bookingForm();
    if (!form) {
      return;
    }
    form.reset();
    const bookingId = form.querySelector('[name="booking_id"]');
    if (bookingId) {
      bookingId.value = '';
    }
    const heading = document.querySelector('#hrm-booking-panel h2');
    if (heading) {
      heading.textContent = i18n('newBooking', 'New Booking');
    }
    renderRatePreview(null);
  }

  function populateBookingForm(booking) {
    const form = bookingForm();
    if (!form || !booking) {
      return;
    }

    const fields = {
      booking_id: booking.booking_id || '',
      room_id: booking.room_id || '',
      check_in: booking.check_in || '',
      check_out: booking.check_out || '',
      guest_name: booking.guest_name || '',
      guest_phone: booking.guest_phone || '',
      guest_email: booking.guest_email || '',
      id_type: booking.id_type || '',
      id_number: booking.id_number || '',
      payment_method: booking.payment_method || 'cash',
      amount_paid: booking.amount_paid || 0,
      transfer_ref: booking.transfer_ref || '',
      status: booking.status || 'confirmed',
      notes: booking.notes || ''
    };

    Object.keys(fields).forEach((name) => {
      const input = form.querySelector(`[name="${name}"]`);
      if (input) {
        input.value = fields[name];
      }
    });

    const quickCheckin = form.querySelector('[name="quick_checkin"]');
    if (quickCheckin) {
      quickCheckin.checked = false;
    }

    const heading = document.querySelector('#hrm-booking-panel h2');
    if (heading) {
      heading.textContent = i18n('editBooking', 'Edit Booking / Switch Room');
    }

    calculateAdminRate();
  }

  function renderRatePreview(rate) {
    const preview = document.querySelector('.hrm-admin-rate-preview');
    if (!preview) {
      return;
    }

    if (!rate) {
      preview.textContent = i18n('selectRoomDates', 'Select a room and dates first.');
      return;
    }

    const rows = (rate.breakdown || []).map((row) => `
      <div class="flex justify-between gap-3 py-1">
        <span>${escapeHtml(row.date)} - ${escapeHtml(row.type)}</span>
        <span class="font-semibold text-slate-700">${escapeHtml(row.rate_formatted || row.rate)}</span>
      </div>
    `).join('');

    preview.innerHTML = `
      <div class="space-y-1">
        ${rows}
        <div class="mt-2 border-t border-slate-200 pt-2">
          <div class="flex justify-between gap-3"><span>Subtotal</span><span class="font-semibold">${escapeHtml(rate.subtotal_formatted)}</span></div>
          <div class="flex justify-between gap-3"><span>VAT ${escapeHtml(rate.vat_rate)}%</span><span class="font-semibold">${escapeHtml(rate.vat_formatted)}</span></div>
          <div class="flex justify-between gap-3 text-base font-bold text-slate-900"><span>Total</span><span>${escapeHtml(rate.total_formatted)}</span></div>
        </div>
      </div>`;
  }

  function calculateAdminRate() {
    const form = bookingForm();
    if (!form) {
      return;
    }

    const roomId = form.querySelector('[name="room_id"]')?.value;
    const checkIn = form.querySelector('[name="check_in"]')?.value;
    const checkOut = form.querySelector('[name="check_out"]')?.value;
    if (!roomId || !checkIn || !checkOut) {
      renderRatePreview(null);
      return;
    }

    const data = new FormData();
    data.set('room_id', roomId);
    data.set('check_in', checkIn);
    data.set('check_out', checkOut);

    request('hrm_admin_calculate_rate', data)
      .then((result) => {
        if (!result || !result.success) {
          renderRatePreview(null);
          return;
        }
        renderRatePreview(result.data.rate);
      })
      .catch(() => renderRatePreview(null));
  }

  function lookupGuestByPhone(phone) {
    const form = bookingForm();
    const warning = document.querySelector('.hrm-guest-warning');
    if (!form || !phone) {
      return;
    }

    const data = new FormData();
    data.set('phone', phone);
    request('hrm_guest_lookup', data).then((result) => {
      const guest = result && result.success && result.data ? result.data.guest : null;
      if (!guest) {
        if (warning) {
          warning.classList.add('hidden');
          warning.textContent = '';
        }
        return;
      }

      const name = form.querySelector('[name="guest_name"]');
      const email = form.querySelector('[name="guest_email"]');
      if (name && !name.value) {
        name.value = guest.full_name || '';
      }
      if (email && !email.value) {
        email.value = guest.email || '';
      }

      if (warning) {
        const flag = String(guest.flag || 'none');
        if (guest.is_blocked) {
          warning.textContent = guest.flag_reason || 'This guest is blacklisted. Manager approval is required.';
          warning.className = 'hrm-guest-warning mt-3 rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-700';
        } else if (flag === 'vip' || guest.is_vip) {
          warning.textContent = 'Returning VIP guest';
          warning.className = 'hrm-guest-warning mt-3 rounded-xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700';
        } else {
          warning.textContent = 'Returning guest';
          warning.className = 'hrm-guest-warning mt-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700';
        }
      }
    });
  }

  function populateGuestProfile(profile) {
    const guest = profile && profile.guest ? profile.guest : null;
    if (!guest) {
      window.showToast(i18n('actionFailed', 'The action could not be completed.'), 'error');
      return;
    }

    const setText = (selector, value) => {
      const node = document.querySelector(selector);
      if (node) {
        node.textContent = value || '';
        node.classList.toggle('hidden', !value);
      }
    };

    setText('.hrm-guest-profile-name', guest.full_name || 'Guest Profile');
    setText('.hrm-guest-profile-contact', [guest.phone, guest.email].filter(Boolean).join(' - '));
    setText('.hrm-guest-profile-flag', String(guest.flag || 'none').replace(/_/g, ' '));
    setText('.hrm-guest-profile-spent', guest.total_spent || '');
    setText('.hrm-guest-profile-last', guest.last_stay || '');
    setText('.hrm-guest-profile-id', [guest.id_type, guest.id_number].filter(Boolean).join(' - '));
    setText('.hrm-guest-profile-reason', guest.flag_reason || '');
    setText('.hrm-guest-profile-notes', guest.notes || '');

    const form = document.querySelector('.hrm-guest-flag-form');
    if (form) {
      const values = {
        guest_id: guest.id,
        flag: guest.flag || 'none',
        flag_reason: guest.flag_reason || '',
        notes: guest.notes || ''
      };
      Object.keys(values).forEach((name) => {
        const field = form.querySelector(`[name="${name}"]`);
        if (field) {
          field.value = values[name];
        }
      });
    }

    const bookingsNode = document.querySelector('.hrm-guest-bookings');
    if (bookingsNode) {
      const bookings = Array.isArray(profile.bookings) ? profile.bookings : [];
      bookingsNode.innerHTML = bookings.length ? bookings.map((booking) => `
        <div class="rounded-xl bg-surface-50 p-3">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-sm font-semibold text-slate-900">${escapeHtml(booking.ref)} - ${escapeHtml(booking.room)}</p>
              <p class="mt-1 text-xs text-slate-500">${escapeHtml(booking.dates)}</p>
            </div>
            <span class="rounded-full bg-white px-2 py-1 text-xs font-semibold text-slate-600">${escapeHtml(booking.status)}</span>
          </div>
          <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
            <span>${escapeHtml(booking.payment_status)}</span>
            <span class="font-semibold text-slate-800">${escapeHtml(booking.total)}</span>
          </div>
        </div>
      `).join('') : `<div class="rounded-xl bg-surface-50 px-4 py-6 text-center text-sm text-slate-500">No stays yet.</div>`;
    }

    window.openModal('hrm-guest-modal');
  }

  function downloadCsv(filename, content) {
    const blob = new Blob([content || ''], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename || 'hrm-report.csv';
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  }

  function activateReportTab(tab) {
    document.querySelectorAll('.hrm-report-tab').forEach((button) => {
      const active = button.dataset.tab === tab;
      button.classList.toggle('bg-primary-500', active);
      button.classList.toggle('text-white', active);
      button.classList.toggle('text-slate-600', !active);
      button.classList.toggle('hover:bg-surface-50', !active);
    });
    document.querySelectorAll('.hrm-report-panel').forEach((panel) => {
      panel.classList.toggle('hidden', panel.dataset.panel !== tab);
    });
  }

  document.addEventListener('click', (event) => {
    const billingButton = event.target.closest('.hrm-billing-toggle');
    if (billingButton) {
      updateBillingCycle(billingButton.dataset.cycle);
      return;
    }

    const reportTab = event.target.closest('.hrm-report-tab');
    if (reportTab) {
      activateReportTab(reportTab.dataset.tab || 'room-performance');
      return;
    }

    const exportButton = event.target.closest('.hrm-export-report');
    if (exportButton) {
      const data = new FormData();
      data.set('report_type', exportButton.dataset.report || '');
      data.set('from', exportButton.dataset.from || '');
      data.set('to', exportButton.dataset.to || '');
      exportButton.disabled = true;
      request('hrm_export_csv', data)
        .then((result) => {
          if (result && result.success && result.data) {
            downloadCsv(result.data.filename, result.data.content);
            window.showToast(i18n('csvExported', 'CSV exported.'), 'success');
          } else {
            handleJsonResult(result, '');
          }
          exportButton.disabled = false;
        })
        .catch(() => {
          exportButton.disabled = false;
          window.showToast(i18n('actionFailed', 'The action could not be completed.'), 'error');
        });
      return;
    }

    const testWhatsAppButton = event.target.closest('.hrm-test-whatsapp');
    if (testWhatsAppButton) {
      const phoneInput = document.querySelector('.hrm-whatsapp-test-phone');
      const data = new FormData();
      data.set('phone', phoneInput ? phoneInput.value.trim() : '');
      testWhatsAppButton.disabled = true;
      window.showToast(i18n('whatsappSending', 'Sending WhatsApp message...'), 'info');
      request('hrm_test_whatsapp', data)
        .then((result) => {
          handleJsonResult(result, i18n('whatsappSent', 'WhatsApp message sent.'));
          testWhatsAppButton.disabled = false;
        })
        .catch(() => {
          testWhatsAppButton.disabled = false;
          window.showToast(i18n('actionFailed', 'The action could not be completed.'), 'error');
        });
      return;
    }

    const planButton = event.target.closest('.hrm-plan-pay');
    if (planButton) {
      const data = new FormData();
      data.set('hotel_id', planButton.dataset.hotelId || adminConfig().currentHotelId || '');
      data.set('plan', planButton.dataset.plan || 'basic');
      data.set('billing_cycle', billingCycle);
      planButton.disabled = true;
      window.showToast(i18n('paymentStarting', 'Preparing secure Paystack checkout...'), 'info');
      request('hrm_initiate_plan_payment', data)
        .then((result) => {
          if (result && result.success && result.data && result.data.authorization_url) {
            window.location.href = result.data.authorization_url;
            return;
          }
          handleJsonResult(result, '');
          planButton.disabled = false;
        })
        .catch(() => {
          planButton.disabled = false;
          window.showToast(i18n('paymentUnavailable', 'Paystack checkout could not be started.'), 'error');
        });
      return;
    }

    const refreshGridButton = event.target.closest('.hrm-refresh-room-grid');
    if (refreshGridButton) {
      refreshRoomGrid(false);
      return;
    }

    const roomFilterButton = event.target.closest('.hrm-room-filter');
    if (roomFilterButton) {
      const kind = roomFilterButton.dataset.filterKind;
      const value = roomFilterButton.dataset.filterValue || 'all';
      if (kind === 'type' || kind === 'status') {
        roomFilters[kind] = value;
        updateFilterButtons(kind, value);
        applyRoomFilters();
      }
      return;
    }

    const newRoomButton = event.target.closest('.hrm-new-room');
    if (newRoomButton) {
      resetRoomForm();
      return;
    }

    const newBookingButton = event.target.closest('.hrm-new-booking');
    if (newBookingButton) {
      resetBookingForm();
      return;
    }

    const editBookingButton = event.target.closest('.hrm-edit-booking');
    if (editBookingButton) {
      try {
        populateBookingForm(JSON.parse(editBookingButton.dataset.booking || '{}'));
        window.openSlideOver('hrm-booking-panel');
      } catch (error) {
        window.showToast(i18n('actionFailed', 'The action could not be completed.'), 'error');
      }
      return;
    }

    const editRoomButton = event.target.closest('.hrm-edit-room');
    if (editRoomButton) {
      try {
        populateRoomForm(JSON.parse(editRoomButton.dataset.room || '{}'));
        window.openModal('hrm-room-modal');
      } catch (error) {
        window.showToast(i18n('actionFailed', 'The action could not be completed.'), 'error');
      }
      return;
    }

    const deleteRoomButton = event.target.closest('.hrm-delete-room');
    if (deleteRoomButton) {
      if (!window.confirm(i18n('confirmDeleteRoom', 'Delete this room?'))) {
        return;
      }
      const data = new FormData();
      data.set('room_id', deleteRoomButton.dataset.roomId || '');
      request('hrm_delete_room', data).then((result) => {
        if (handleJsonResult(result, 'Room deleted.')) {
          setTimeout(() => window.location.reload(), 700);
        }
      });
      return;
    }

    const cleanButton = event.target.closest('.hrm-mark-room-clean');
    if (cleanButton) {
      const data = new FormData();
      data.set('room_id', cleanButton.dataset.roomId || '');
      request('hrm_mark_room_clean', data).then((result) => {
        if (handleJsonResult(result, 'Room marked available.')) {
          setTimeout(() => window.location.reload(), 700);
        }
      });
      return;
    }

    const housekeepingButton = event.target.closest('.hrm-housekeeping-status');
    if (housekeepingButton) {
      const data = new FormData();
      data.set('room_id', housekeepingButton.dataset.roomId || '');
      data.set('status', housekeepingButton.dataset.status || 'available');
      request('hrm_mark_room_clean', data).then((result) => {
        if (handleJsonResult(result, 'Housekeeping status updated.')) {
          setTimeout(() => window.location.reload(), 700);
        }
      });
      return;
    }

    const whatsAppButton = event.target.closest('.hrm-send-whatsapp');
    if (whatsAppButton) {
      const data = new FormData();
      data.set('booking_id', whatsAppButton.dataset.bookingId || '');
      data.set('message_type', whatsAppButton.dataset.messageType || 'confirmation');
      whatsAppButton.disabled = true;
      window.showToast(i18n('whatsappSending', 'Sending WhatsApp message...'), 'info');
      request('hrm_send_whatsapp', data)
        .then((result) => {
          handleJsonResult(result, i18n('whatsappSent', 'WhatsApp message sent.'));
          whatsAppButton.disabled = false;
        })
        .catch(() => {
          whatsAppButton.disabled = false;
          window.showToast(i18n('actionFailed', 'The action could not be completed.'), 'error');
        });
      return;
    }

    const guestButton = event.target.closest('.hrm-view-guest');
    if (guestButton) {
      try {
        populateGuestProfile(JSON.parse(guestButton.dataset.profile || '{}'));
      } catch (error) {
        window.showToast(i18n('actionFailed', 'The action could not be completed.'), 'error');
      }
      return;
    }

    const toggleButton = event.target.closest('.hrm-toggle-subscription');
    if (toggleButton) {
      const data = new FormData();
      data.set('hotel_id', toggleButton.dataset.hotelId || '');
      data.set('mode', toggleButton.dataset.mode || 'disable');
      request('hrm_super_toggle_subscription', data).then((result) => {
        if (handleJsonResult(result, i18n('saved', 'Saved successfully.'))) {
          setTimeout(() => window.location.reload(), 700);
        }
      });
      return;
    }

    const impersonateButton = event.target.closest('.hrm-impersonate');
    if (impersonateButton) {
      const data = new FormData();
      data.set('hotel_id', impersonateButton.dataset.hotelId || '');
      request('hrm_super_impersonate', data).then((result) => handleJsonResult(result, i18n('saved', 'Saved successfully.')));
      return;
    }

    const exitButton = event.target.closest('.hrm-exit-impersonation');
    if (exitButton) {
      request('hrm_super_exit_impersonate', new FormData()).then((result) => handleJsonResult(result, i18n('saved', 'Saved successfully.')));
      return;
    }

    const deleteButton = event.target.closest('.hrm-delete-hotel');
    if (deleteButton) {
      if (!window.confirm(i18n('confirmDeleteHotel', 'Delete this hotel and all of its HMS data?'))) {
        return;
      }
      const data = new FormData();
      data.set('hotel_id', deleteButton.dataset.hotelId || '');
      request('hrm_super_delete_hotel', data).then((result) => {
        if (handleJsonResult(result, i18n('saved', 'Saved successfully.'))) {
          setTimeout(() => window.location.reload(), 700);
        }
      });
      return;
    }

    const subscriptionAction = event.target.closest('.hrm-subscription-action');
    if (subscriptionAction) {
      const actionType = subscriptionAction.dataset.actionType || '';
      if (actionType === 'cancel' && !window.confirm(i18n('confirmCancel', 'Cancel this subscription?'))) {
        return;
      }
      const data = new FormData();
      data.set('subscription_id', subscriptionAction.dataset.subscriptionId || '');
      data.set('subscription_action', actionType);
      if (actionType === 'mark_paid') {
        const amount = window.prompt(i18n('enterAmount', 'Enter amount paid in Naira'), '0');
        if (amount === null) {
          return;
        }
        data.set('amount_paid', amount);
      }
      request('hrm_super_subscription_action', data).then((result) => {
        if (handleJsonResult(result, i18n('saved', 'Saved successfully.'))) {
          setTimeout(() => window.location.reload(), 700);
        }
      });
      return;
    }

    const testModeButton = event.target.closest('.hrm-toggle-test-mode');
    if (testModeButton) {
      const data = new FormData();
      data.set('enabled', testModeButton.dataset.enabled || '0');
      request('hrm_super_toggle_test_mode', data).then((result) => {
        if (handleJsonResult(result, i18n('saved', 'Saved successfully.'))) {
          setTimeout(() => window.location.reload(), 700);
        }
      });
    }
  });

  document.addEventListener('change', (event) => {
    const roomStatus = event.target.closest('.hrm-room-status-toggle');
    if (roomStatus) {
      if (!window.confirm(i18n('confirmStatusChange', 'Update this status?'))) {
        roomStatus.value = roomStatus.dataset.currentStatus || roomStatus.value;
        return;
      }
      const data = new FormData();
      data.set('room_id', roomStatus.dataset.roomId || '');
      data.set('status', roomStatus.value || '');
      request('hrm_toggle_room_status', data).then((result) => {
        if (handleJsonResult(result, 'Room status updated.')) {
          roomStatus.dataset.currentStatus = roomStatus.value;
          const row = roomStatus.closest('.hrm-filterable-room');
          if (row) {
            row.dataset.roomStatus = roomStatus.value;
            applyRoomFilters();
          }
          refreshRoomGrid(true);
        } else {
          roomStatus.value = roomStatus.dataset.currentStatus || roomStatus.value;
        }
      });
      return;
    }

    const bookingStatus = event.target.closest('.hrm-booking-status-toggle');
    if (bookingStatus) {
      if (!window.confirm(i18n('confirmStatusChange', 'Update this status?'))) {
        bookingStatus.value = bookingStatus.dataset.currentStatus || bookingStatus.value;
        return;
      }
      const data = new FormData();
      data.set('booking_id', bookingStatus.dataset.bookingId || '');
      data.set('status', bookingStatus.value || '');
      request('hrm_update_booking_status', data).then((result) => {
        if (handleJsonResult(result, 'Booking status updated.')) {
          bookingStatus.dataset.currentStatus = bookingStatus.value;
          setTimeout(() => window.location.reload(), 700);
        } else {
          bookingStatus.value = bookingStatus.dataset.currentStatus || bookingStatus.value;
        }
      });
      return;
    }

    if (event.target.closest('.hrm-booking-form [name="room_id"], .hrm-booking-form [name="check_in"], .hrm-booking-form [name="check_out"]')) {
      calculateAdminRate();
    }
  });

  document.addEventListener('blur', (event) => {
    const phoneInput = event.target.closest('.hrm-booking-form [name="guest_phone"]');
    if (phoneInput) {
      lookupGuestByPhone(phoneInput.value.trim());
    }
  }, true);

  document.addEventListener('submit', (event) => {
    const form = event.target.closest('.hrm-ajax-form');
    if (!form) {
      return;
    }

    event.preventDefault();
    const action = form.dataset.action;
    if (form.classList.contains('hrm-guest-flag-form') && !window.confirm(i18n('confirmFlagGuest', 'Save this guest flag?'))) {
      return;
    }
    if (form.classList.contains('hrm-settings-form') && form.querySelector('[name="remove_staff_ids[]"]:checked') && !window.confirm(i18n('confirmRemoveStaff', 'Remove selected staff access?'))) {
      return;
    }
    const submitButton = form.querySelector('[type="submit"]');
    if (submitButton) {
      submitButton.disabled = true;
    }

    request(action, new FormData(form))
      .then((result) => {
        if (handleJsonResult(result, i18n('saved', 'Saved successfully.'))) {
          setTimeout(() => window.location.reload(), 700);
        } else if (submitButton) {
          submitButton.disabled = false;
        }
      })
      .catch(() => {
        if (submitButton) {
          submitButton.disabled = false;
        }
        window.showToast(i18n('actionFailed', 'The action could not be completed.'), 'error');
      });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      window.closeUpgradeModal();
      const openModal = document.querySelector('[id^="hrm-"].fixed:not(.hidden)');
      if (openModal) {
        window.closeModal(openModal.id);
      }
    }
  });

  updateBillingCycle('yearly');
  refreshRoomGrid(true);
  if (document.querySelector('.hrm-room-grid-live[data-autorefresh="1"]')) {
    window.setInterval(() => refreshRoomGrid(true), 30000);
  }
})();
