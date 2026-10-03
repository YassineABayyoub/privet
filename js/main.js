document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;
  const roleToggle = document.querySelector('[data-role-toggle]');

  if (roleToggle) {
    const currentRole = body.dataset.role || 'judge';
    roleToggle.textContent = currentRole === 'clerk' ? 'واجهة العدل' : 'واجهة الكاتبة';
  }

  const sidebar = document.querySelector('.sidebar');
  const mobileToggle = document.querySelector('.mobile-toggle');
  const overlay = document.querySelector('.sidebar-overlay');

  if (mobileToggle && sidebar) {
    const syncSidebarState = () => {
      if (overlay) {
        overlay.classList.toggle('is-visible', sidebar.classList.contains('is-open'));
      }
    };

    const closeSidebar = () => {
      sidebar.classList.remove('is-open');
      syncSidebarState();
    };

    mobileToggle.addEventListener('click', () => {
      sidebar.classList.toggle('is-open');
      syncSidebarState();
    });

    if (overlay) {
      overlay.addEventListener('click', closeSidebar);
    }

    document.addEventListener('click', (event) => {
      if (window.innerWidth > 960) return;
      if (!sidebar.contains(event.target) && !mobileToggle.contains(event.target)) {
        closeSidebar();
      }
    });
  }

  const fileInput = document.querySelector('#documentUpload');
  const uploadList = document.querySelector('#uploadList');
  if (fileInput && uploadList) {
    let selectedFiles = [];
    let previewUrls = [];

    const renderSelectedFiles = () => {
      previewUrls.forEach((url) => URL.revokeObjectURL(url));
      previewUrls = [];
      uploadList.replaceChildren();
      selectedFiles.forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'upload-item';
        const info = document.createElement('div');
        const fileName = document.createElement('strong');
        fileName.textContent = file.name;
        const metadata = document.createElement('div');
        metadata.className = 'small-muted';
        metadata.textContent = `${file.type || 'غير محدد'} · ${(file.size / 1024).toFixed(1)} KB`;
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'action-btn danger';
        removeButton.textContent = 'حذف';
        removeButton.addEventListener('click', () => {
          selectedFiles.splice(index, 1);
          const transfer = new DataTransfer();
          selectedFiles.forEach((selectedFile) => transfer.items.add(selectedFile));
          fileInput.files = transfer.files;
          renderSelectedFiles();
        });
        info.append(fileName, metadata);
        if (file.type.startsWith('image/')) {
          const preview = document.createElement('img');
          preview.className = 'upload-preview';
          preview.src = URL.createObjectURL(file);
          previewUrls.push(preview.src);
          preview.alt = `معاينة ${file.name}`;
          info.appendChild(preview);
        }
        item.append(info, removeButton);
        uploadList.appendChild(item);
      });
    };

    fileInput.addEventListener('change', () => {
      selectedFiles = Array.from(fileInput.files || []);
      renderSelectedFiles();
    });
  }

  const addPersonBtn = document.querySelector('[data-add-person]');
  const personsContainer = document.querySelector('#personsContainer');
  if (addPersonBtn && personsContainer) {
    let nextPersonIndex = Array.from(personsContainer.querySelectorAll('[name^="personnes["]'))
      .reduce((nextIndex, field) => {
        const match = field.name.match(/^personnes\[(\d+)\]/);
        return match ? Math.max(nextIndex, Number(match[1]) + 1) : nextIndex;
      }, 0);

    const addPersonBlock = () => {
      const index = nextPersonIndex++;
      const block = document.createElement('div');
      block.className = 'person-block';
      block.innerHTML = `
        <div class="person-block-header">
          <h4></h4>
          <button type="button" class="action-btn danger remove-person">حذف</button>
        </div>
        <div class="person-grid">
          <div class="field">
            <label>الدور</label>
            <input type="text" name="personnes[${index}][role]" maxlength="100" />
          </div>
          <div class="field">
            <label>الاسم</label>
            <input type="text" name="personnes[${index}][nom]" maxlength="120" placeholder="اسم الشخص" />
          </div>
          <div class="field">
            <label>النسب</label>
            <input type="text" name="personnes[${index}][prenom]" maxlength="160" placeholder="النسب" />
          </div>
          <div class="field">
            <label>رقم البطاقة</label>
            <input type="text" name="personnes[${index}][cin]" maxlength="80" placeholder="0000000" />
          </div>
        </div>
      `;
      personsContainer.appendChild(block);
      return block;
    };

    let marriagePartyForm = false;
    const configurePartyForm = () => {
      const currentType = document.querySelector('#contractType, #type');
      marriagePartyForm = currentType?.value === 'عقد الزواج';

      if (marriagePartyForm) {
        while (personsContainer.querySelectorAll('.person-block').length < 2) {
          addPersonBlock().dataset.marriageCreated = 'true';
        }
      } else {
        personsContainer.querySelectorAll('[data-marriage-created]').forEach((block) => {
          const identityFields = block.querySelectorAll('[name$=\"[nom]\"], [name$=\"[prenom]\"], [name$=\"[cin]\"]');
          if (Array.from(identityFields).every((input) => input.value.trim() === '')) {
            block.remove();
          }
        });
      }

      const currentBlocks = Array.from(personsContainer.querySelectorAll('.person-block'));
      currentBlocks.forEach((block, index) => {
        const roleInput = block.querySelector('[name$="[role]"]');
        const firstNameInput = block.querySelector('[name$="[nom]"]');
        const lastNameInput = block.querySelector('[name$="[prenom]"]');
        const title = block.querySelector('.person-block-header h4');
        const removeButton = block.querySelector('.remove-person');

        if (marriagePartyForm) {
          const role = index === 0 ? 'الزوج' : index === 1 ? 'الزوجة' : 'شاهد';
          if (index >= 2) {
            block.dataset.marriageCreated = 'true';
          }
          roleInput.value = role;
          roleInput.dataset.marriageRole = role;
          roleInput.readOnly = true;
          title.textContent = index < 2 ? role : `الشاهد ${index - 1}`;
          firstNameInput.required = true;
          lastNameInput.required = true;
          removeButton.hidden = index < 2;
        } else {
          if (roleInput.dataset.marriageRole === roleInput.value) {
            roleInput.value = '';
          }
          delete roleInput.dataset.marriageRole;
          roleInput.readOnly = false;
          title.textContent = `الشخص ${index + 1}`;
          firstNameInput.required = false;
          lastNameInput.required = false;
          removeButton.hidden = false;
        }
      });
      addPersonBtn.textContent = marriagePartyForm ? '+ إضافة شاهد' : '+ إضافة شخص';
    };

    personsContainer.addEventListener('click', (event) => {
      const removeButton = event.target.closest('.remove-person');
      if (!removeButton) return;
      const block = removeButton.closest('.person-block');
      const blocks = Array.from(personsContainer.querySelectorAll('.person-block'));
      if (marriagePartyForm && blocks.indexOf(block) < 2) return;
      block.remove();
      configurePartyForm();
    });

    addPersonBtn.addEventListener('click', () => {
      addPersonBlock();
      configurePartyForm();
    });

    configurePartyForm();
    personsContainer.addEventListener('contract-type-change', configurePartyForm);
  }

  const categorySelect = document.querySelector('#contractCategory, #category');
  const typeSelect = document.querySelector('#contractType, #type');
  const contractConfig = {
    'الزواج': ['عقد الزواج', 'عقد الطلاق', 'عقد الرجعة'],
    'الأملاك': ['عقد البيع', 'عقد الهبة', 'عقد الصدقة', 'عقد القسمة'],
    'التركات': ['الإراثة', 'حصر التركة', 'المخارجة'],
    'مختلفة': ['الوكالة', 'الإقرار', 'الصلح']
  };
  const propertyConfig = {
    'سيارة': {
      make_model: 'العلامة والطراز',
      registration: 'رقم التسجيل',
      chassis: 'رقم الهيكل',
      color: 'اللون'
    },
    'بقعة أرضية': {
      area: 'المساحة',
      location: 'الموقع',
      parcel_reference: 'رقم الرسم أو القطعة',
      boundaries: 'الحدود'
    },
    'منزل': {
      address: 'العنوان أو الموقع',
      area: 'المساحة',
      floors: 'عدد الطوابق',
      title_reference: 'مرجع الملكية'
    },
    'محل تجاري': {
      location: 'الموقع',
      area: 'المساحة',
      title_reference: 'مرجع الملكية',
      commercial_activity: 'النشاط التجاري'
    },
    'أخرى': {
      description: 'الوصف'
    }
  };
  const propertiesContainer = document.querySelector('#propertiesContainer');
  const addPropertyButton = document.querySelector('[data-add-property]');
  const propertiesSection = document.querySelector('[data-properties-fields]');
  let nextPropertyIndex = 0;

  if (propertiesContainer && addPropertyButton && propertiesSection) {
    const addCustomCharacteristic = (card, entry = {}, customIndex = null) => {
      const customFields = card.querySelector('[data-custom-characteristics]');
      const index = customIndex === null
        ? Number(customFields.dataset.nextIndex || 0)
        : customIndex;
      customFields.dataset.nextIndex = String(Math.max(Number(customFields.dataset.nextIndex || 0), index + 1));
      const row = document.createElement('div');
      row.className = 'person-grid';

      const labelField = document.createElement('div');
      labelField.className = 'field';
      const label = document.createElement('label');
      label.textContent = 'اسم الخاصية';
      const labelInput = document.createElement('input');
      labelInput.type = 'text';
      labelInput.maxLength = 80;
      labelInput.name = `properties[${card.dataset.index}][custom_characteristics][${index}][label]`;
      labelInput.value = entry.label || '';
      labelField.append(label, labelInput);

      const valueField = document.createElement('div');
      valueField.className = 'field';
      const valueLabel = document.createElement('label');
      valueLabel.textContent = 'القيمة';
      const valueInput = document.createElement('input');
      valueInput.type = 'text';
      valueInput.maxLength = 2000;
      valueInput.name = `properties[${card.dataset.index}][custom_characteristics][${index}][value]`;
      valueInput.value = entry.value || '';
      valueField.append(valueLabel, valueInput);

      const removeField = document.createElement('div');
      removeField.className = 'field';
      const removeButton = document.createElement('button');
      removeButton.type = 'button';
      removeButton.className = 'action-btn danger';
      removeButton.textContent = 'حذف الخاصية';
      removeButton.addEventListener('click', () => row.remove());
      removeField.appendChild(removeButton);
      row.append(labelField, valueField, removeField);
      customFields.appendChild(row);
    };

    const renderPropertyCharacteristics = (card, property) => {
      const fields = card.querySelector('[data-property-characteristics]');
      fields.replaceChildren();
      const type = card.querySelector('[data-property-type]').value;
      Object.entries(propertyConfig[type] || {}).forEach(([key, label]) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'field';
        const fieldLabel = document.createElement('label');
        fieldLabel.textContent = label;
        const input = document.createElement('input');
        input.type = 'text';
        input.name = `properties[${card.dataset.index}][characteristics][${key}]`;
        input.maxLength = 2000;
        input.value = property.characteristics?.[key] || '';
        wrapper.append(fieldLabel, input);
        fields.appendChild(wrapper);
      });

      const customFields = card.querySelector('[data-custom-characteristics]');
      customFields.replaceChildren();
      (property.custom_characteristics || []).forEach((entry, index) => {
        addCustomCharacteristic(card, entry, index);
      });
    };

    const addPropertyCard = (initial = {}) => {
      const index = nextPropertyIndex++;
      const property = {
        type: initial.type || '',
        characteristics: { ...(initial.characteristics || {}) },
        custom_characteristics: [...(initial.custom_characteristics || [])]
      };
      const card = document.createElement('div');
      card.className = 'person-block property-block';
      card.dataset.index = String(index);

      const header = document.createElement('div');
      header.className = 'person-block-header';
      const title = document.createElement('h4');
      title.textContent = `ملك ${propertiesContainer.querySelectorAll('.property-block').length + 1}`;
      const removeButton = document.createElement('button');
      removeButton.type = 'button';
      removeButton.className = 'action-btn danger';
      removeButton.textContent = 'حذف الملك';
      removeButton.addEventListener('click', () => card.remove());
      header.append(title, removeButton);

      const typeField = document.createElement('div');
      typeField.className = 'field';
      const typeLabel = document.createElement('label');
      typeLabel.textContent = 'نوع الملك';
      const typeSelect = document.createElement('select');
      typeSelect.dataset.propertyType = '';
      typeSelect.name = `properties[${index}][type]`;
      typeSelect.required = true;
      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.textContent = 'اختر نوع الملك';
      typeSelect.appendChild(placeholder);
      Object.keys(propertyConfig).forEach((type) => {
        const option = document.createElement('option');
        option.value = type;
        option.textContent = type;
        typeSelect.appendChild(option);
      });
      typeSelect.value = property.type;
      typeSelect.addEventListener('change', () => {
        property.type = typeSelect.value;
        property.characteristics = {};
        property.custom_characteristics = [];
        renderPropertyCharacteristics(card, property);
        title.textContent = `${typeSelect.value || 'ملك'} ${Array.from(propertiesContainer.querySelectorAll('.property-block')).indexOf(card) + 1}`;
      });
      typeField.append(typeLabel, typeSelect);

      const fields = document.createElement('div');
      fields.className = 'form-grid';
      fields.dataset.propertyCharacteristics = '';
      const customFields = document.createElement('div');
      customFields.dataset.customCharacteristics = '';
      const addCharacteristicButton = document.createElement('button');
      addCharacteristicButton.type = 'button';
      addCharacteristicButton.className = 'btn btn-secondary';
      addCharacteristicButton.textContent = '+ إضافة خاصية أخرى';
      addCharacteristicButton.addEventListener('click', () => addCustomCharacteristic(card));

      card.append(header, typeField, fields, customFields, addCharacteristicButton);
      propertiesContainer.appendChild(card);
      renderPropertyCharacteristics(card, property);
      return card;
    };

    let initialProperties = [];
    try {
      initialProperties = JSON.parse(propertiesContainer.dataset.initialProperties || '[]');
    } catch (error) {
      console.error('Could not read saved property form data.', error);
    }
    if (Array.isArray(initialProperties)) {
      initialProperties.forEach((property) => addPropertyCard(property));
    }
    propertiesContainer.addEventListener('add-initial-property', () => {
      if (propertiesContainer.childElementCount === 0) {
        addPropertyCard();
      }
    });
    addPropertyButton.addEventListener('click', () => addPropertyCard());
  }

  if (categorySelect && typeSelect) {
    const updateConditionalSections = () => {
      document.querySelectorAll('[data-properties-section]').forEach((section) => {
        const visible = categorySelect.value === 'الأملاك';
        section.hidden = !visible;
        section.querySelectorAll('input, select, textarea, button').forEach((control) => {
          control.disabled = !visible;
        });
        if (visible && propertiesContainer && propertiesContainer.childElementCount === 0) {
          propertiesContainer.dispatchEvent(new CustomEvent('add-initial-property'));
        }
      });
      document.querySelectorAll('[data-marriage-help]').forEach((help) => {
        help.hidden = typeSelect.value !== 'عقد الزواج';
      });
      document.querySelectorAll('[data-party-help]').forEach((help) => {
        help.hidden = typeSelect.value === 'عقد الزواج';
      });
      if (personsContainer && addPersonBtn) {
        personsContainer.dispatchEvent(new CustomEvent('contract-type-change'));
      }
    };

    const updateTypeOptions = () => {
      const selected = categorySelect.value;
      const types = contractConfig[selected] || contractConfig['الأملاك'];
      const requestedType = typeSelect.value || typeSelect.dataset.selectedType;
      typeSelect.innerHTML = '';
      types.forEach((type) => {
        const option = document.createElement('option');
        option.value = type;
        option.textContent = type;
        typeSelect.appendChild(option);
      });
      if (types.includes(requestedType)) {
        typeSelect.value = requestedType;
      }
      typeSelect.dataset.selectedType = '';
      updateConditionalSections();
    };

    categorySelect.addEventListener('change', updateTypeOptions);
    typeSelect.addEventListener('change', updateConditionalSections);
    updateTypeOptions();
  }

  const searchInput = document.querySelector('[data-search-input]');
  const filterSelect = document.querySelector('[data-category-filter]');
  const tableBody = document.querySelector('[data-contract-table-body]');
  if (searchInput && tableBody) {
    const rows = Array.from(tableBody.querySelectorAll('tr[data-contract-row]'));
    const applyFilters = () => {
      const term = searchInput.value.trim();
      const category = filterSelect ? filterSelect.value : 'الكل';

      rows.forEach((row) => {
        const text = row.textContent.toLowerCase();
        const matchesText = !term || text.includes(term.toLowerCase());
        const matchesCategory = category === 'الكل' || row.dataset.category === category;
        row.hidden = !(matchesText && matchesCategory);
      });
    };

    searchInput.addEventListener('input', applyFilters);
    if (filterSelect) filterSelect.addEventListener('change', applyFilters);
  }

  const roleButtons = document.querySelectorAll('[data-role-button]');
  roleButtons.forEach((button) => {
    button.addEventListener('click', () => {
      roleButtons.forEach((el) => el.classList.remove('active'));
      button.classList.add('active');
    });
  });
});
