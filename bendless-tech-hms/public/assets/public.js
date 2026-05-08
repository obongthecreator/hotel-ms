(function () {
  'use strict';

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function parseConfig(app) {
    try {
      return JSON.parse(app.dataset.config || '{}');
    } catch (error) {
      return {};
    }
  }

  function showToast(message, type) {
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
    toast.className = `fixed bottom-6 right-6 z-[100] flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-white shadow-modal transition-all duration-300 ${colors[type] || colors.info}`;
    toast.innerHTML = `<span class="iconify text-lg" data-icon="${icons[type] || icons.info}"></span><span>${escapeHtml(message)}</span>`;
    document.body.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(20px)';
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  }

  function initBookingApp(app) {
    const config = parseConfig(app);
    const state = {
      currentView: 'listing',
      selectedRoom: null,
      selectedRate: null,
      galleryImages: [],
      galleryIndex: 0,
      search: {
        checkIn: config.defaultCheckIn,
        checkOut: config.defaultCheckOut,
        guests: 1
      },
      availability: {}
    };

    const rooms = Array.isArray(config.rooms) ? config.rooms : [];
    const searchForm = app.querySelector('.hrm-search-form');

    if (!searchForm) {
      return;
    }

    function roomById(roomId) {
      return rooms.find((room) => String(room.id) === String(roomId));
    }

    function api(action, data) {
      const body = new FormData();
      body.set('action', action);
      body.set('nonce', config.nonce || '');
      body.set('hotel_id', config.hotelId || '');
      Object.keys(data || {}).forEach((key) => body.set(key, data[key]));

      return fetch(config.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body
      }).then((response) => response.json());
    }

    function money(amount) {
      return `${config.currencySymbol || '₦'}${Number(amount || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      })}`;
    }

    function setView(view) {
      state.currentView = view;
      app.querySelectorAll('.hrm-view').forEach((node) => {
        node.classList.toggle('hidden', node.dataset.view !== view);
      });
      window.scrollTo({ top: app.getBoundingClientRect().top + window.scrollY - 24, behavior: 'smooth' });
    }

    function updateSearchFromForm(form) {
      state.search.checkIn = form.querySelector('[name="check_in"]').value;
      state.search.checkOut = form.querySelector('[name="check_out"]').value;
      state.search.guests = parseInt(form.querySelector('[name="guests"]').value || '1', 10);
      app.querySelector('[name="detail_check_in"]').value = state.search.checkIn;
      app.querySelector('[name="detail_check_out"]').value = state.search.checkOut;
      app.querySelector('[name="detail_guests"]').value = state.search.guests;
    }

    function syncSearchMinDates() {
      const listingCheckIn = app.querySelector('[name="check_in"]');
      const listingCheckOut = app.querySelector('[name="check_out"]');
      const detailCheckIn = app.querySelector('[name="detail_check_in"]');
      const detailCheckOut = app.querySelector('[name="detail_check_out"]');
      [listingCheckIn, detailCheckIn].forEach((input) => {
        if (input) {
          input.min = config.defaultCheckIn;
        }
      });
      [listingCheckOut, detailCheckOut].forEach((input) => {
        if (input) {
          input.min = state.search.checkIn || config.defaultCheckOut;
        }
      });
    }

    function applyAvailability(payload) {
      state.availability = {};
      (payload || []).forEach((row) => {
        state.availability[String(row.room_id)] = row;
      });

      app.querySelectorAll('.hrm-room-card').forEach((card) => {
        const row = state.availability[card.dataset.roomId];
        const available = !row || row.available;
        card.dataset.available = available ? '1' : '0';
        card.classList.toggle('opacity-60', !available);

        const badge = card.querySelector('.hrm-availability-badge');
        if (badge) {
          badge.className = `hrm-availability-badge absolute right-4 top-4 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ${
            available ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'
          }`;
          badge.innerHTML = `<span class="h-1.5 w-1.5 rounded-full ${available ? 'bg-emerald-500' : 'bg-red-500'}"></span>${available ? 'Available' : 'Unavailable'}`;
        }

        const price = card.querySelector('.hrm-room-price');
        if (price && row && row.price_formatted) {
          price.textContent = row.price_formatted;
        }
      });
    }

    function runAvailabilitySearch(form) {
      updateSearchFromForm(form);
      syncSearchMinDates();

      return api('hrm_get_room_availability', {
        check_in: state.search.checkIn,
        check_out: state.search.checkOut,
        guests: state.search.guests
      }).then((result) => {
        if (!result || !result.success) {
          showToast(result && result.data && result.data.message ? result.data.message : 'Availability could not be checked.', 'error');
          return false;
        }
        applyAvailability(result.data.availability || []);
        showToast('Availability updated.', 'success');
        return true;
      });
    }

    function imageMarkup(room, image, index) {
      if (image) {
        return `<button type="button" class="hrm-gallery-open h-full w-full" data-gallery-index="${index}"><img src="${escapeHtml(image)}" alt="${escapeHtml('Room ' + room.room_number)}" class="h-full w-full object-cover"></button>`;
      }
      return `<div class="flex h-full w-full items-center justify-center bg-gradient-to-br ${escapeHtml(room.placeholder || 'from-slate-800 to-primary-700')} text-white/80"><span class="iconify text-6xl" data-icon="solar:bed-linear"></span></div>`;
    }

    function renderRoomDetail(room) {
      state.selectedRoom = room;
      state.galleryImages = room.images && room.images.length ? room.images : [];
      state.galleryIndex = 0;

      const title = app.querySelector('.hrm-detail-title');
      const type = app.querySelector('.hrm-detail-type');
      const meta = app.querySelector('.hrm-detail-meta');
      const price = app.querySelector('.hrm-detail-price');
      const description = app.querySelector('.hrm-detail-description');
      const mainImage = app.querySelector('.hrm-detail-main-image');
      const thumbs = app.querySelector('.hrm-detail-thumbs');
      const amenities = app.querySelector('.hrm-detail-amenities');

      title.textContent = `Room ${room.room_number}`;
      type.textContent = String(room.room_type || '').replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
      meta.textContent = `Floor ${room.floor} · Up to ${room.max_guests} guests`;
      price.textContent = room.price_formatted || money(room.price_per_night);
      description.textContent = room.description || 'A comfortable room prepared for a relaxed stay.';
      mainImage.innerHTML = imageMarkup(room, state.galleryImages[0], 0);
      thumbs.innerHTML = '';

      const thumbImages = state.galleryImages.length ? state.galleryImages.slice(1, 5) : ['', '', '', ''];
      thumbImages.forEach((image, index) => {
        const wrap = document.createElement('div');
        wrap.className = 'aspect-[4/3] overflow-hidden rounded-2xl bg-surface-900';
        wrap.innerHTML = imageMarkup(room, image, index + 1);
        thumbs.appendChild(wrap);
      });

      amenities.innerHTML = '';
      (room.amenities || []).forEach((amenity) => {
        const icon = config.amenityIcons && config.amenityIcons[String(amenity).toLowerCase()] ? config.amenityIcons[String(amenity).toLowerCase()] : 'solar:check-circle-linear';
        const node = document.createElement('div');
        node.className = 'flex items-center gap-3 rounded-xl bg-surface-50 px-4 py-3 text-sm font-medium text-slate-700';
        node.innerHTML = `<span class="iconify text-primary-500" data-icon="${escapeHtml(icon)}"></span><span>${escapeHtml(amenity)}</span>`;
        amenities.appendChild(node);
      });

      if (!amenities.children.length) {
        amenities.innerHTML = '<div class="text-sm text-slate-500">Amenities will be confirmed by the hotel.</div>';
      }

      app.querySelector('[name="detail_check_in"]').value = state.search.checkIn;
      app.querySelector('[name="detail_check_out"]').value = state.search.checkOut;
      app.querySelector('[name="detail_guests"]').value = state.search.guests;
      calculateDetailRate();
      setView('detail');
    }

    function renderRate(rate) {
      state.selectedRate = rate;
      app.querySelector('.hrm-breakdown-nights').textContent = `${rate.nights} night${rate.nights === 1 ? '' : 's'}`;
      app.querySelector('.hrm-breakdown-subtotal').textContent = rate.subtotal_formatted;
      app.querySelector('.hrm-breakdown-vat-label').textContent = `VAT (${Number(rate.vat_rate).toFixed(2)}%)`;
      app.querySelector('.hrm-breakdown-vat').textContent = rate.vat_formatted;
      app.querySelector('.hrm-breakdown-total').textContent = rate.total_formatted;

      const nightly = app.querySelector('.hrm-nightly-breakdown');
      const specialRates = (rate.breakdown || []).filter((row) => row.type !== 'standard');
      nightly.classList.toggle('hidden', specialRates.length === 0);
      nightly.innerHTML = specialRates.map((row) => {
        return `<div class="flex justify-between gap-3 py-1"><span>${escapeHtml(row.date)} · ${escapeHtml(row.type)}</span><span class="font-semibold text-slate-700">${escapeHtml(row.rate_formatted)}</span></div>`;
      }).join('');

      app.querySelector('.hrm-book-now').disabled = false;
      app.querySelector('.hrm-book-now').classList.remove('bg-slate-300', 'hover:bg-slate-300', 'cursor-not-allowed');
      app.querySelector('.hrm-book-now').classList.add('bg-primary-500', 'hover:bg-primary-600');
    }

    function renderUnavailableButton(message) {
      const button = app.querySelector('.hrm-book-now');
      button.disabled = true;
      button.textContent = message || 'Not Available for Selected Dates';
      button.classList.remove('bg-primary-500', 'hover:bg-primary-600');
      button.classList.add('bg-slate-300', 'hover:bg-slate-300', 'cursor-not-allowed');
    }

    function selectedPaymentMethod() {
      const selected = app.querySelector('[name="payment_method"]:checked');
      return selected ? selected.value : 'paystack';
    }

    function updateCheckoutPaymentUi() {
      const method = selectedPaymentMethod();
      const total = state.selectedRate && state.selectedRate.total_formatted ? state.selectedRate.total_formatted : money(0);
      const bankPanel = app.querySelector('.hrm-bank-transfer-panel');
      const transferRef = app.querySelector('[name="transfer_ref"]');
      const securityText = app.querySelector('.hrm-payment-security-text');
      const payText = app.querySelector('.hrm-pay-button-text');

      app.querySelectorAll('.hrm-payment-option').forEach((option) => {
        const input = option.querySelector('[name="payment_method"]');
        const active = input && input.checked;
        option.classList.toggle('border-primary-500', active);
        option.classList.toggle('bg-primary-50', active);
        option.classList.toggle('text-slate-900', active);
        option.classList.toggle('border-slate-200', !active);
        option.classList.toggle('bg-white', !active);
        option.classList.toggle('text-slate-700', !active);
      });

      if (bankPanel) {
        bankPanel.classList.toggle('hidden', method !== 'transfer');
      }
      if (transferRef) {
        transferRef.required = method === 'transfer';
      }
      if (securityText) {
        securityText.textContent = method === 'transfer' ? 'Bank transfer details supplied by the hotel' : 'Secured by Paystack';
      }
      if (payText) {
        payText.textContent = method === 'transfer' ? `Submit ${total} transfer booking` : `Pay ${total} with Paystack`;
      }
    }

    function calculateDetailRate() {
      if (!state.selectedRoom) {
        return Promise.resolve(false);
      }

      const checkIn = app.querySelector('[name="detail_check_in"]').value;
      const checkOut = app.querySelector('[name="detail_check_out"]').value;
      const guests = app.querySelector('[name="detail_guests"]').value;
      state.search.checkIn = checkIn;
      state.search.checkOut = checkOut;
      state.search.guests = parseInt(guests || '1', 10);
      syncSearchMinDates();

      app.querySelector('.hrm-book-now').innerHTML = '<span class="iconify" data-icon="solar:refresh-linear"></span> Checking...';
      app.querySelector('.hrm-book-now').disabled = true;

      return api('hrm_calculate_booking_price', {
        room_id: state.selectedRoom.id,
        check_in: checkIn,
        check_out: checkOut,
        guests
      }).then((result) => {
        app.querySelector('.hrm-book-now').innerHTML = '<span class="iconify" data-icon="solar:calendar-mark-linear"></span> Book Now';
        if (!result || !result.success) {
          state.selectedRate = null;
          renderUnavailableButton(result && result.data && result.data.message ? result.data.message : 'Not Available for Selected Dates');
          return false;
        }
        renderRate(result.data.rate);
        return true;
      });
    }

    function renderCheckout() {
      if (!state.selectedRoom || !state.selectedRate) {
        showToast('Please select available dates before checkout.', 'warning');
        return;
      }

      const room = state.selectedRoom;
      const rate = state.selectedRate;
      const imageBox = app.querySelector('.hrm-summary-image');
      imageBox.innerHTML = imageMarkup(room, room.images && room.images[0], 0);
      app.querySelector('.hrm-summary-room').textContent = `Room ${room.room_number} · ${String(room.room_type).replace(/_/g, ' ')}`;
      app.querySelector('.hrm-summary-dates').textContent = `${state.search.checkIn} - ${state.search.checkOut}`;
      app.querySelector('.hrm-summary-nights').textContent = `${rate.nights} night${rate.nights === 1 ? '' : 's'}`;
      app.querySelector('.hrm-summary-subtotal').textContent = rate.subtotal_formatted;
      app.querySelector('.hrm-summary-vat-label').textContent = `VAT (${Number(rate.vat_rate).toFixed(2)}%)`;
      app.querySelector('.hrm-summary-vat').textContent = rate.vat_formatted;
      app.querySelector('.hrm-summary-total').textContent = rate.total_formatted;
      app.querySelector('.hrm-summary-breakdown').innerHTML = (rate.breakdown || []).map((row) => {
        return `<div class="flex justify-between gap-3 py-1"><span>${escapeHtml(row.date)} · ${escapeHtml(row.type)}</span><span class="font-semibold text-slate-700">${escapeHtml(row.rate_formatted)}</span></div>`;
      }).join('');
      updateCheckoutPaymentUi();

      setView('checkout');
    }

    function openGallery(index) {
      if (!state.galleryImages.length) {
        return;
      }
      state.galleryIndex = Math.max(0, Math.min(state.galleryImages.length - 1, index));
      const modal = app.querySelector('.hrm-gallery-modal');
      const image = app.querySelector('.hrm-gallery-image');
      image.src = state.galleryImages[state.galleryIndex];
      image.alt = `Room ${state.selectedRoom.room_number}`;
      modal.classList.remove('hidden');
      modal.classList.add('flex');
    }

    function closeGallery() {
      const modal = app.querySelector('.hrm-gallery-modal');
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }

    function stepGallery(direction) {
      if (!state.galleryImages.length) {
        return;
      }
      state.galleryIndex = (state.galleryIndex + direction + state.galleryImages.length) % state.galleryImages.length;
      app.querySelector('.hrm-gallery-image').src = state.galleryImages[state.galleryIndex];
    }

    searchForm.addEventListener('submit', (event) => {
      event.preventDefault();
      runAvailabilitySearch(event.currentTarget);
    });

    app.querySelectorAll('.hrm-filter-pill').forEach((button) => {
      button.addEventListener('click', () => {
        const filter = button.dataset.filter;
        app.querySelectorAll('.hrm-filter-pill').forEach((node) => {
          const active = node === button;
          node.classList.toggle('border-primary-500', active);
          node.classList.toggle('bg-primary-50', active);
          node.classList.toggle('text-primary-600', active);
          node.classList.toggle('border-slate-200', !active);
          node.classList.toggle('bg-white', !active);
          node.classList.toggle('text-slate-600', !active);
        });
        app.querySelectorAll('.hrm-room-card').forEach((card) => {
          card.classList.toggle('hidden', filter !== 'all' && card.dataset.roomType !== filter);
        });
      });
    });

    app.addEventListener('click', (event) => {
      const viewButton = event.target.closest('.hrm-view-room');
      if (viewButton && app.contains(viewButton)) {
        const room = roomById(viewButton.dataset.roomId);
        if (room) {
          renderRoomDetail(room);
        }
        return;
      }

      if (event.target.closest('.hrm-back-listing')) {
        setView('listing');
        return;
      }

      if (event.target.closest('.hrm-back-detail')) {
        setView('detail');
        return;
      }

      const galleryOpen = event.target.closest('.hrm-gallery-open');
      if (galleryOpen && app.contains(galleryOpen)) {
        openGallery(parseInt(galleryOpen.dataset.galleryIndex || '0', 10));
        return;
      }

      if (event.target.closest('.hrm-gallery-close')) {
        closeGallery();
        return;
      }

      if (event.target.closest('.hrm-gallery-prev')) {
        stepGallery(-1);
        return;
      }

      if (event.target.closest('.hrm-gallery-next')) {
        stepGallery(1);
      }
    });

    app.querySelector('.hrm-detail-booking-form').addEventListener('submit', (event) => {
      event.preventDefault();
      calculateDetailRate().then((ok) => {
        if (ok) {
          renderCheckout();
        }
      });
    });

    ['detail_check_in', 'detail_check_out', 'detail_guests'].forEach((name) => {
      const input = app.querySelector(`[name="${name}"]`);
      if (input) {
        input.addEventListener('change', calculateDetailRate);
      }
    });

    app.querySelector('[name="phone"]').addEventListener('blur', (event) => {
      const phone = event.currentTarget.value;
      if (!phone) {
        return;
      }
      api('hrm_public_guest_lookup', { phone }).then((result) => {
        const guest = result && result.success && result.data ? result.data.guest : null;
        const returningBadge = app.querySelector('.hrm-returning-guest');
        const blacklistWarning = app.querySelector('.hrm-blacklist-warning');
        returningBadge.classList.toggle('hidden', !guest);
        blacklistWarning.classList.toggle('hidden', !guest || !guest.is_blocked);
        app.querySelector('.hrm-pay-button').disabled = !!(guest && guest.is_blocked);
        if (guest && !guest.is_blocked) {
          app.querySelector('[name="full_name"]').value = guest.full_name || '';
          app.querySelector('[name="email"]').value = guest.email || '';
        }
      });
    });

    app.querySelectorAll('[name="payment_method"]').forEach((input) => {
      input.addEventListener('change', updateCheckoutPaymentUi);
    });

    app.querySelector('.hrm-checkout-form').addEventListener('submit', (event) => {
      event.preventDefault();
      if (!state.selectedRoom || !state.selectedRate) {
        showToast('Booking summary is missing.', 'error');
        return;
      }

      const form = event.currentTarget;
      const button = app.querySelector('.hrm-pay-button');
      const formData = new FormData(form);
      const paymentMethod = selectedPaymentMethod();
      const payload = {
        room_id: state.selectedRoom.id,
        check_in: state.search.checkIn,
        check_out: state.search.checkOut,
        guests: state.search.guests,
        full_name: formData.get('full_name') || '',
        phone: formData.get('phone') || '',
        email: formData.get('email') || '',
        id_type: formData.get('id_type') || '',
        id_number: formData.get('id_number') || '',
        notes: formData.get('notes') || '',
        terms: formData.get('terms') ? '1' : '0',
        payment_method: paymentMethod,
        transfer_ref: formData.get('transfer_ref') || ''
      };

      button.disabled = true;
      button.querySelector('.hrm-pay-button-text').textContent = paymentMethod === 'transfer' ? 'Submitting transfer booking...' : 'Preparing checkout...';

      api('hrm_initiate_booking_payment', payload).then((result) => {
        if (result && result.success && result.data && result.data.authorization_url) {
          window.location.href = result.data.authorization_url;
          return;
        }
        if (result && result.success && result.data && result.data.confirmation_url) {
          window.location.href = result.data.confirmation_url;
          return;
        }
        button.disabled = false;
        updateCheckoutPaymentUi();
        showToast(result && result.data && result.data.message ? result.data.message : 'Payment could not be started.', 'error');
      }).catch(() => {
        button.disabled = false;
        updateCheckoutPaymentUi();
        showToast('Payment could not be started.', 'error');
      });
    });

    document.addEventListener('keydown', (event) => {
      if (state.currentView !== 'detail') {
        return;
      }
      if (event.key === 'Escape') {
        closeGallery();
      }
      if (event.key === 'ArrowLeft') {
        stepGallery(-1);
      }
      if (event.key === 'ArrowRight') {
        stepGallery(1);
      }
    });

    const listingForm = searchForm;
    updateSearchFromForm(listingForm);
    syncSearchMinDates();
    runAvailabilitySearch(listingForm);
  }

  function initFrontendDashboard(app) {
    const config = parseConfig(app);
    const roomFilters = {
      type: 'all',
      status: 'all'
    };

    function api(action, data) {
      const body = new FormData();
      if (data instanceof FormData) {
        data.forEach((value, key) => body.append(key, value));
      } else {
        Object.keys(data || {}).forEach((key) => body.set(key, data[key]));
      }
      body.set('action', action);
      body.set('nonce', config.nonce || '');

      return fetch(config.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body
      }).then((response) => response.json());
    }

    function rowData(control) {
      const row = control.closest('[data-room]');
      if (!row) {
        return {};
      }
      try {
        return JSON.parse(row.dataset.room || '{}');
      } catch (error) {
        return {};
      }
    }

    function bookingData(control) {
      const row = control.closest('[data-booking]');
      if (!row) {
        return {};
      }
      try {
        return JSON.parse(row.dataset.booking || '{}');
      } catch (error) {
        return {};
      }
    }

    function restoreSelect(select) {
      if (select.dataset.previousValue) {
        select.value = select.dataset.previousValue;
      }
    }

    function updateRoomFilterButtons(kind, value) {
      app.querySelectorAll(`.hrm-front-room-filter[data-filter-kind="${kind}"]`).forEach((button) => {
        const active = button.dataset.filterValue === value;
        button.classList.toggle('border-primary-500', active);
        button.classList.toggle('bg-primary-500', active);
        button.classList.toggle('text-white', active);
        button.classList.toggle('border-slate-200', !active);
        button.classList.toggle('bg-white', !active);
        button.classList.toggle('text-black', !active);
        button.classList.toggle('hover:bg-primary-50', !active);
      });
    }

    function applyRoomFilters() {
      app.querySelectorAll('.hrm-front-filterable-room').forEach((row) => {
        const typeMatches = roomFilters.type === 'all' || row.dataset.roomType === roomFilters.type;
        const statusMatches = roomFilters.status === 'all' || row.dataset.roomStatus === roomFilters.status;
        row.classList.toggle('hidden', !(typeMatches && statusMatches));
      });
    }

    app.querySelectorAll('.hrm-front-room-status, .hrm-front-booking-status').forEach((select) => {
      select.dataset.previousValue = select.value;
    });

    app.addEventListener('change', (event) => {
      const roomStatus = event.target.closest('.hrm-front-room-status');
      if (roomStatus && app.contains(roomStatus)) {
        roomStatus.disabled = true;
        api('hrm_toggle_room_status', {
          room_id: roomStatus.dataset.roomId || '',
          status: roomStatus.value
        }).then((result) => {
          roomStatus.disabled = false;
          if (!result || !result.success) {
            restoreSelect(roomStatus);
            showToast(result && result.data && result.data.message ? result.data.message : 'Room status could not be updated.', 'error');
            return;
          }
          roomStatus.dataset.previousValue = roomStatus.value;
          const row = roomStatus.closest('.hrm-front-filterable-room');
          if (row) {
            row.dataset.roomStatus = roomStatus.value;
            applyRoomFilters();
          }
          showToast('Room status updated.', 'success');
        }).catch(() => {
          roomStatus.disabled = false;
          restoreSelect(roomStatus);
          showToast('Room status could not be updated.', 'error');
        });
        return;
      }

      const bookingStatus = event.target.closest('.hrm-front-booking-status');
      if (bookingStatus && app.contains(bookingStatus)) {
        bookingStatus.disabled = true;
        api('hrm_update_booking_status', {
          booking_id: bookingStatus.dataset.bookingId || '',
          status: bookingStatus.value
        }).then((result) => {
          bookingStatus.disabled = false;
          if (!result || !result.success) {
            restoreSelect(bookingStatus);
            showToast(result && result.data && result.data.message ? result.data.message : 'Booking status could not be updated.', 'error');
            return;
          }
          bookingStatus.dataset.previousValue = bookingStatus.value;
          showToast('Booking status updated.', 'success');
        }).catch(() => {
          bookingStatus.disabled = false;
          restoreSelect(bookingStatus);
          showToast('Booking status could not be updated.', 'error');
        });
      }
    });

    app.addEventListener('click', (event) => {
      const filterButton = event.target.closest('.hrm-front-room-filter');
      if (filterButton && app.contains(filterButton)) {
        const kind = filterButton.dataset.filterKind;
        const value = filterButton.dataset.filterValue || 'all';
        if (kind === 'type' || kind === 'status') {
          roomFilters[kind] = value;
          updateRoomFilterButtons(kind, value);
          applyRoomFilters();
        }
        return;
      }

      const savePrice = event.target.closest('.hrm-front-save-room-price');
      if (savePrice && app.contains(savePrice)) {
        const room = rowData(savePrice);
        const row = savePrice.closest('[data-room]');
        const priceInput = row ? row.querySelector('.hrm-front-room-price') : null;
        const statusSelect = row ? row.querySelector('.hrm-front-room-status') : null;
        savePrice.disabled = true;
        api('hrm_save_room', {
          room_id: room.id || savePrice.dataset.roomId || '',
          room_number: room.room_number || '',
          room_type: room.room_type || 'single',
          floor: room.floor || 1,
          price_per_night: priceInput ? priceInput.value : room.price_per_night || 0,
          weekend_rate: room.weekend_rate || '',
          peak_rate: room.peak_rate || '',
          max_guests: room.max_guests || 2,
          status: statusSelect ? statusSelect.value : room.status || 'available',
          description: room.description || '',
          amenities: room.amenities || '',
          image_urls: room.image_urls || ''
        }).then((result) => {
          savePrice.disabled = false;
          if (!result || !result.success) {
            showToast(result && result.data && result.data.message ? result.data.message : 'Room price could not be saved.', 'error');
            return;
          }
          if (priceInput) {
            room.price_per_night = priceInput.value;
            row.dataset.room = JSON.stringify(room);
          }
          showToast('Room price saved.', 'success');
        }).catch(() => {
          savePrice.disabled = false;
          showToast('Room price could not be saved.', 'error');
        });
        return;
      }

      const switchRoom = event.target.closest('.hrm-front-switch-booking-room');
      if (switchRoom && app.contains(switchRoom)) {
        const wrapper = switchRoom.closest('[data-booking]');
        const select = wrapper ? wrapper.querySelector('.hrm-front-switch-room-select') : null;
        const booking = bookingData(switchRoom);
        if (!select || !booking.booking_id) {
          return;
        }
        switchRoom.disabled = true;
        api('hrm_save_booking', {
          booking_id: booking.booking_id,
          room_id: select.value,
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
        }).then((result) => {
          switchRoom.disabled = false;
          if (!result || !result.success) {
            showToast(result && result.data && result.data.message ? result.data.message : 'Room could not be switched.', 'error');
            return;
          }
          showToast('Room switched.', 'success');
          setTimeout(() => window.location.reload(), 650);
        }).catch(() => {
          switchRoom.disabled = false;
          showToast('Room could not be switched.', 'error');
        });
        return;
      }

      const deleteButton = event.target.closest('.hrm-front-delete-room');
      if (deleteButton && app.contains(deleteButton)) {
        if (!window.confirm('Delete this room?')) {
          return;
        }
        deleteButton.disabled = true;
        api('hrm_delete_room', {
          room_id: deleteButton.dataset.roomId || ''
        }).then((result) => {
          if (!result || !result.success) {
            deleteButton.disabled = false;
            showToast(result && result.data && result.data.message ? result.data.message : 'Room could not be deleted.', 'error');
            return;
          }
          const row = deleteButton.closest('[data-room]');
          if (row) {
            row.remove();
          }
          showToast('Room deleted.', 'success');
        }).catch(() => {
          deleteButton.disabled = false;
          showToast('Room could not be deleted.', 'error');
        });
      }
    });

    const settingsForm = app.querySelector('.hrm-frontend-settings-form');
    if (settingsForm) {
      settingsForm.addEventListener('submit', (event) => {
        event.preventDefault();
        if (settingsForm.querySelector('[name="remove_staff_ids[]"]:checked') && !window.confirm('Remove selected staff access?')) {
          return;
        }
        const submit = settingsForm.querySelector('[type="submit"]');
        if (submit) {
          submit.disabled = true;
        }
        api('hrm_save_settings', new FormData(settingsForm)).then((result) => {
          if (submit) {
            submit.disabled = false;
          }
          if (!result || !result.success) {
            showToast(result && result.data && result.data.message ? result.data.message : 'Settings could not be saved.', 'error');
            return;
          }
          showToast('Frontend settings saved.', 'success');
          setTimeout(() => window.location.reload(), 650);
        }).catch(() => {
          if (submit) {
            submit.disabled = false;
          }
          showToast('Settings could not be saved.', 'error');
        });
      });
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.hrm-public-booking[data-config]').forEach(initBookingApp);
    document.querySelectorAll('.hrm-frontend-dashboard[data-config]').forEach(initFrontendDashboard);
  });
})();
