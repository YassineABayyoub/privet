(() => {
  const translations = {
    'أرشيف العقود العدلية': 'Archives des contrats adoulaires',
    'أرشيف العقود': 'Archives des contrats',
    'أرشيف العدول': 'Archives des adouls',
    'تسجيل الدخول': 'Connexion',
    'إضافة ملك': 'Ajouter un bien',
    '+ إضافة ملك': '+ Ajouter un bien',
    '+ إضافة شخص': '+ Ajouter une personne',
    '+ إضافة شاهد': '+ Ajouter un témoin',
    'التاريخ': 'Date',
    'العدد': 'Nombre',
    'الوثائق الممسوحة ضوئياً': 'Documents numérisés',
    'اختر مستندات PDF أو JPG أو PNG (10 ميغابايت كحد أقصى لكل ملف)': 'Choisissez des documents PDF, JPG ou PNG (10 Mo maximum par fichier)',
    'معلومات عامة إضافية، دون افتراض حقول قانونية غير معتمدة.': 'Informations complémentaires générales, sans supposer de champs juridiques non approuvés.',
    'منصة العدلية': 'Plateforme adoulaire',
    'التنقل الرئيسي': 'Navigation principale',
    'الرئيسية': 'Accueil',
    'لوحة التحكم': 'Tableau de bord',
    'العقود': 'Contrats',
    'إضافة عقد': 'Ajouter un contrat',
    'إضافة عقد جديد': 'Ajouter un nouveau contrat',
    '+ إضافة عقد جديد': '+ Ajouter un nouveau contrat',
    'تعديل العقد': 'Modifier le contrat',
    'تحديث المعلومات العامة والأطراف المرتبطة': 'Mettre à jour les informations générales et les parties associées',
    'تفاصيل الشخص': 'Détails de la personne',
    'تفاصيل الطرف وسجل العقود المرتبط به': 'Détails de la partie et historique de ses contrats',
    'رجوع إلى الأشخاص': 'Retour aux personnes',
    'عدد العقود': 'Nombre de contrats',
    'النوع': 'Type',
    'الدور': 'Rôle',
    'الأشخاص': 'Personnes',
    'المستخدمون': 'Utilisateurs',
    'التقارير': 'Rapports',
    'الإعدادات': 'Paramètres',
    'المستخدم الحالي': 'Utilisateur actuel',
    'العدل': 'Adoul',
    'الكاتبة': 'Greffière',
    'تسجيل الخروج': 'Déconnexion',
    'واجهة العدل': 'Interface adoul',
    'واجهة الكاتبة': 'Interface greffière',
    'عرض': 'Voir',
    'عرض العقد': 'Voir le contrat',
    'عرض / تحميل': 'Voir / télécharger',
    'تعديل': 'Modifier',
    'حذف': 'Supprimer',
    'حذف نهائياً': 'Supprimer définitivement',
    'إعادة تفعيل': 'Réactiver',
    'تعطيل': 'Désactiver',
    'نشط': 'Actif',
    'معطل': 'Désactivé',
    'بحث': 'Rechercher',
    'إعادة ضبط': 'Réinitialiser',
    'السابق': 'Précédent',
    'التالي': 'Suivant',
    'كل التصنيفات': 'Toutes les catégories',
    'كل الأنواع': 'Tous les types',
    'التصنيف': 'Catégorie',
    'نوع العقد': 'Type de contrat',
    'رقم العقد': 'Numéro du contrat',
    'تاريخ العقد': 'Date du contrat',
    'تاريخ التسجيل': 'Date d’enregistrement',
    'رقم الأرشيف': 'Numéro d’archive',
    'المرجع': 'Référence',
    'ملاحظات عامة (اختياري)': 'Notes générales (facultatif)',
    'ملاحظات إضافية (اختياري)': 'Notes supplémentaires (facultatif)',
    'ملاحظات إضافية': 'Notes supplémentaires',
    'ملاحظات': 'Notes',
    'الخصائص': 'Caractéristiques',
    'ملك': 'Bien',
    'الأطراف': 'Parties',
    'الطرف': 'Partie',
    'الاسم': 'Prénom',
    'الاسم الظاهر': 'Nom affiché',
    'النسب': 'Nom de famille',
    'رقم البطاقة': 'Numéro de pièce d’identité',
    'رقم البطاقة (اختياري)': 'Numéro de pièce d’identité (facultatif)',
    'مثال: الطرف': 'Exemple : partie',
    'الزوج': 'Époux',
    'الزوجة': 'Épouse',
    'شاهد': 'Témoin',
    'الشاهد': 'Témoin',
    'إضافة شاهد': 'Ajouter un témoin',
    'إضافة شخص': 'Ajouter une personne',
    'الشخص': 'Personne',
    'إضافة خاصية أخرى': 'Ajouter une autre caractéristique',
    'إضافة خاصية': 'Ajouter une caractéristique',
    'حذف الخاصية': 'Supprimer la caractéristique',
    'حذف الملك': 'Supprimer le bien',
    'نوع الملك': 'Type de bien',
    'اختر نوع الملك': 'Choisir le type de bien',
    'اسم الخاصية': 'Nom de la caractéristique',
    'القيمة': 'Valeur',
    'معلومات العقد': 'Informations du contrat',
    'اختيار التصنيف والنوع': 'Choisir la catégorie et le type',
    'معلومات الأملاك وخصائصها': 'Informations sur les biens et leurs caractéristiques',
    'اختر نوع كل ملك لإظهار خصائصه. يمكنك إضافة أكثر من ملك.': 'Choisissez le type de chaque bien pour afficher ses caractéristiques. Vous pouvez ajouter plusieurs biens.',
    'يمكن ربط الشخص نفسه بعدة عقود باستخدام رقم تعريفه إن توفر.': 'Une même personne peut être liée à plusieurs contrats à l’aide de son numéro d’identité, s’il est disponible.',
    'أدخل الزوج والزوجة؛ ويمكن إضافة الشاهد اختيارياً.': 'Saisissez l’époux et l’épouse ; un témoin peut être ajouté facultativement.',
    'عودة إلى القائمة': 'Retour à la liste',
    'رجوع': 'Retour',
    'إلغاء': 'Annuler',
    'طباعة': 'Imprimer',
    'حفظ التعديلات': 'Enregistrer les modifications',
    'حفظ التغييرات': 'Enregistrer les modifications',
    'حفظ العقد': 'Enregistrer le contrat',
    'الأملاك': 'Biens',
    'الزواج': 'Mariage',
    'التركات': 'Successions',
    'مختلفة': 'Divers',
    'عقد الزواج': 'Acte de mariage',
    'عقد الطلاق': 'Acte de divorce',
    'عقد الرجعة': 'Acte de reprise de vie commune',
    'عقد البيع': 'Acte de vente',
    'عقد الهبة': 'Acte de donation',
    'عقد الصدقة': 'Acte de donation charitable',
    'عقد القسمة': 'Acte de partage',
    'الإراثة': 'Hérédité',
    'حصر التركة': 'Inventaire de succession',
    'المخارجة': 'Partage successoral',
    'الوكالة': 'Procuration',
    'الإقرار': 'Déclaration',
    'الصلح': 'Conciliation',
    'سيارة': 'Voiture',
    'بقعة أرضية': 'Terrain',
    'منزل': 'Maison',
    'محل تجاري': 'Local commercial',
    'أخرى': 'Autre',
    'العلامة والطراز': 'Marque et modèle',
    'رقم التسجيل': 'Numéro d’immatriculation',
    'رقم الهيكل': 'Numéro de châssis',
    'اللون': 'Couleur',
    'المساحة': 'Superficie',
    'الموقع': 'Emplacement',
    'رقم الرسم أو القطعة': 'Numéro du titre ou de la parcelle',
    'الحدود': 'Limites',
    'العنوان أو الموقع': 'Adresse ou emplacement',
    'عدد الطوابق': 'Nombre d’étages',
    'مرجع الملكية': 'Référence de propriété',
    'النشاط التجاري': 'Activité commerciale',
    'الوصف': 'Description',
    'مجموع العقود': 'Nombre total de contrats',
    'إجمالي السجلات': 'Total des enregistrements',
    'عقود الزواج': 'Contrats de mariage',
    'عقود الأملاك': 'Contrats relatifs aux biens',
    'عقود التركات': 'Contrats de succession',
    'عقود مختلفة': 'Contrats divers',
    'عدد الأشخاص': 'Nombre de personnes',
    'أطراف مرتبطة بالعقود': 'Parties liées aux contrats',
    'المستخدمون النشطون': 'Utilisateurs actifs',
    'حسابات مفعلة': 'Comptes activés',
    'إحصاءات مباشرة من قاعدة بيانات الأرشيف': 'Statistiques en direct de la base de données des archives',
    'بحث سريع': 'Recherche rapide',
    'البحث السريع': 'Recherche rapide',
    'رقم العقد أو اسم الطرف أو المرجع': 'Numéro du contrat, nom d’une partie ou référence',
    'الفئة': 'Catégorie',
    'فتح الأرشيف': 'Ouvrir les archives',
    'آخر العقود المسجلة': 'Derniers contrats enregistrés',
    'عرض الكل': 'Tout afficher',
    'لا توجد عقود في قاعدة البيانات بعد. ابدأ بإضافة أول عقد.': 'Aucun contrat dans la base de données. Commencez par ajouter le premier contrat.',
    'حسب التصنيف': 'Par catégorie',
    'قائمة الأشخاص المرتبطين بعقود محفوظة': 'Liste des personnes liées aux contrats enregistrés',
    'ابحث بالاسم أو رقم البطاقة': 'Rechercher par nom ou numéro de pièce d’identité',
    'لا توجد بيانات أشخاص': 'Aucune donnée de personne',
    'لا توجد بيانات أشخاص.': 'Aucune donnée de personne.',
    'لا توجد بيانات أشخاص مطابقة للبحث.': 'Aucune personne ne correspond à la recherche.',
    'مطابقة للبحث': 'correspondant à la recherche',
    'العقود المرتبطة': 'Contrats associés',
    'الإجراء': 'Action',
    'الإجراءات': 'Actions',
    'النتائج': 'Résultats',
    'العقد': 'Contrat',
    'شخص': 'personne',
    'من': 'sur',
    'قائمة العقود': 'Liste des contrats',
    'بحث وتصفية السجلات المحفوظة في قاعدة البيانات': 'Rechercher et filtrer les enregistrements de la base de données',
    'رقم العقد، اسم الطرف، رقم البطاقة، المرجع': 'Numéro du contrat, nom d’une partie, pièce d’identité ou référence',
    'لا توجد عقود مطابقة. أضف عقداً أو غيّر معايير البحث.': 'Aucun contrat correspondant. Ajoutez un contrat ou modifiez les critères de recherche.',
    'لا توجد عقود في هذه الصفحة.': 'Aucun contrat sur cette page.',
    'الأطراف المرتبطة': 'Parties associées',
    'تفاصيل العقد': 'Détails du contrat',
    'المعلومات العامة': 'Informations générales',
    'أُدخل بواسطة': 'Saisi par',
    'لا توجد أطراف مرتبطة.': 'Aucune partie associée.',
    'الوثائق المرفقة': 'Documents joints',
    'لا توجد وثائق مرفقة.': 'Aucun document joint.',
    'معلومات إضافية خاصة بالعقد': 'Informations complémentaires sur le contrat',
    'ملخصات محسوبة من بيانات العقود الموجودة في قاعدة البيانات': 'Résumés calculés à partir des contrats enregistrés dans la base de données',
    'العقود حسب التصنيف': 'Contrats par catégorie',
    'لا توجد بيانات كافية لإعداد التقرير.': 'Données insuffisantes pour établir le rapport.',
    'العقود حسب الشهر (آخر 12 شهراً)': 'Contrats par mois (12 derniers mois)',
    'الشهر': 'Mois',
    'لا توجد عقود ضمن الفترة المحددة.': 'Aucun contrat sur la période sélectionnée.',
    'تحديث بيانات حسابك وكلمة المرور': 'Mettre à jour les informations de votre compte et votre mot de passe',
    'لتغيير اسم المستخدم، أدخل كلمة المرور الحالية.': 'Pour modifier votre nom d’utilisateur, saisissez le mot de passe actuel.',
    'تغيير كلمة المرور (اختياري)': 'Modifier le mot de passe (facultatif)',
    'كلمة المرور الحالية': 'Mot de passe actuel',
    'كلمة المرور الجديدة': 'Nouveau mot de passe',
    'تأكيد كلمة المرور': 'Confirmer le mot de passe',
    'كلمة المرور (12 حرفاً على الأقل)': 'Mot de passe (12 caractères minimum)',
    'كلمة المرور يجب أن تحتوي على 12 حرفاً على الأقل.': 'Le mot de passe doit contenir au moins 12 caractères.',
    'الحسابات المسجلة': 'Comptes enregistrés',
    'إضافة مستخدم': 'Ajouter un utilisateur',
    'إنشاء الحساب': 'Créer le compte',
    'إجراء غير معروف.': 'Action inconnue.',
    'اسم المستخدم مستخدم مسبقاً.': 'Ce nom d’utilisateur est déjà utilisé.',
    'اسم المستخدم مستخدم من حساب آخر.': 'Ce nom d’utilisateur est utilisé par un autre compte.',
    'المستخدم غير موجود.': 'Utilisateur introuvable.',
    'معرف المستخدم غير صالح.': 'Identifiant utilisateur invalide.',
    'لا يمكنك تعطيل حسابك الحالي.': 'Vous ne pouvez pas désactiver votre compte actuel.',
    'لا يمكنك حذف حسابك الحالي.': 'Vous ne pouvez pas supprimer votre compte actuel.',
    'يجب الاحتفاظ بحساب مسؤول واحد نشط على الأقل.': 'Au moins un compte administrateur actif doit être conservé.',
    'لا يمكن حذف آخر حساب مسؤول نشط.': 'Le dernier compte administrateur actif ne peut pas être supprimé.',
    'لا يمكن حذف هذا الحساب لأنه مرتبط بعقود أو وثائق محفوظة. يمكنك تعطيله بدلاً من ذلك.': 'Ce compte est lié à des contrats ou documents enregistrés et ne peut pas être supprimé. Vous pouvez le désactiver.',
    'لا يمكن حذف هذا الحساب لأنه مرتبط بسجلات محفوظة. يمكنك تعطيله بدلاً من ذلك.': 'Ce compte est lié à des enregistrements et ne peut pas être supprimé. Vous pouvez le désactiver.',
    'الدور المختار غير صالح.': 'Le rôle sélectionné est invalide.',
    'أدخل اسماً ظاهراً صالحاً.': 'Saisissez un nom affiché valide.',
    'اسم المستخدم يجب أن يكون 3-80 حرفاً/رقماً أو . _ -': 'Le nom d’utilisateur doit comporter de 3 à 80 lettres/chiffres ou . _ -',
    'اسم المستخدم يجب أن يتكون من 3 إلى 80 حرفاً لاتينياً أو رقماً أو . _ -': 'Le nom d’utilisateur doit comporter de 3 à 80 caractères latins, chiffres ou . _ -',
    'اسم المستخدم يجب أن يكون من 3 إلى 80 حرفاً/رقماً، أو . _ -': 'Le nom d’utilisateur doit comporter de 3 à 80 lettres/chiffres ou . _ -',
    'كلمة المرور يجب ألا تقل عن 12 حرفاً.': 'Le mot de passe doit comporter au moins 12 caractères.',
    'كلمة المرور الجديدة يجب ألا تقل عن 12 حرفاً.': 'Le nouveau mot de passe doit comporter au moins 12 caractères.',
    'تأكيد كلمة المرور غير مطابق.': 'La confirmation du mot de passe ne correspond pas.',
    'أدخل اسماً ظاهراً صالحاً لا يتجاوز 160 حرفاً.': 'Saisissez un nom affiché valide de 160 caractères maximum.',
    'أدخل كلمة المرور الحالية لتغيير اسم المستخدم أو كلمة المرور.': 'Saisissez le mot de passe actuel pour modifier le nom d’utilisateur ou le mot de passe.',
    'كلمة المرور الحالية غير صحيحة.': 'Le mot de passe actuel est incorrect.',
    'تم تحديث إعدادات الحساب.': 'Les paramètres du compte ont été mis à jour.',
    'تم إنشاء حساب المستخدم.': 'Le compte utilisateur a été créé.',
    'تم تفعيل الحساب.': 'Le compte a été activé.',
    'تم تعطيل الحساب.': 'Le compte a été désactivé.',
    'تم حذف حساب المستخدم نهائياً.': 'Le compte utilisateur a été supprimé définitivement.',
    'تم تحديث العقد والأطراف.': 'Le contrat et les parties ont été mis à jour.',
    'تم إنشاء حساب المسؤول. يمكنك تسجيل الدخول الآن.': 'Le compte administrateur a été créé. Vous pouvez maintenant vous connecter.',
    'رقم العقد أو رقم الأرشيف مستخدم مسبقاً.': 'Le numéro du contrat ou le numéro d’archive est déjà utilisé.',
    'رقم العقد أو رقم الأرشيف مستخدم لعقد آخر.': 'Le numéro du contrat ou le numéro d’archive est déjà utilisé par un autre contrat.',
    'رقم العقد أو رقم الأرشيف يتجاوز الحد المسموح.': 'Le numéro du contrat ou le numéro d’archive dépasse la longueur autorisée.',
    'أضف طرفاً واحداً على الأقل مع الاسم والنسب والدور.': 'Ajoutez au moins une partie avec son prénom, son nom et son rôle.',
    'بيانات أحد الأطراف تتجاوز الحد المسموح.': 'Les informations d’une partie dépassent la longueur autorisée.',
    'اسم أحد الملفات طويل جداً.': 'Le nom d’un fichier est trop long.',
    'الملاحظات طويلة جداً.': 'Les notes sont trop longues.',
    'بيانات أحد الأملاك غير صالحة.': 'Les données d’un bien sont invalides.',
    'رقم الأرشيف أو المرجع يتجاوز الحد المسموح.': 'Le numéro d’archive ou la référence dépasse la longueur autorisée.',
    'لا توجد عقود مرتبطة بهذا الشخص.': 'Aucun contrat associé à cette personne.',
    'سيتم حفظ بيانات العقد والأطراف في قاعدة البيانات بعد الإرسال.': 'Les informations du contrat et des parties seront enregistrées après envoi.',
    'الرجاء اختيار نوع العقد.': 'Veuillez choisir le type de contrat.',
    'دور': 'Rôle',
    'اختر نوع العقد': 'Choisir le type de contrat',
    'معلومات التسجيل': 'Informations d’enregistrement',
    'المرفقات': 'Pièces jointes',
    'إضافة ملف': 'Ajouter un fichier',
    'الوثائق': 'Documents',
    'الرجاء اختيار ملف': 'Veuillez choisir un fichier',
    'مسموح بملفات PDF والصور فقط.': 'Seuls les fichiers PDF et images sont autorisés.',
    'إدارة الحسابات': 'Gestion des comptes',
    'حسابات المستخدمين': 'Comptes utilisateurs',
    'بيانات الشخص': 'Informations sur la personne',
    'بحث عن عقد': 'Rechercher un contrat',
    'بحث وتصفية': 'Rechercher et filtrer',
    'حذف هذا العقد؟': 'Supprimer ce contrat ?',
    'هل تريد حذف هذا العقد؟': 'Voulez-vous supprimer ce contrat ?',
    'رقم العقد مطلوب ويجب ألا يتجاوز 80 حرفاً.': 'Le numéro du contrat est obligatoire et ne doit pas dépasser 80 caractères.',
    'التصنيف أو نوع العقد غير صالح.': 'La catégorie ou le type de contrat est invalide.',
    'أدخل الاسم والنسب والدور لكل طرف.': 'Saisissez le prénom, le nom et le rôle de chaque partie.',
    'أضف طرفاً واحداً على الأقل.': 'Ajoutez au moins une partie.',
    'تعذر حذف الملف المرفق.': 'Impossible de supprimer le fichier joint.',
    'سيتم حذف هذا الحساب نهائياً. هل تريد المتابعة؟': 'Ce compte sera supprimé définitivement. Voulez-vous continuer ?',
    'بحث عن مستخدم': 'Rechercher un utilisateur',
    'اختر تصنيفاً ونوع عقد صحيحين.': 'Choisissez une catégorie et un type de contrat valides.',
    'حجم كل ملف يجب ألا يتجاوز 10 ميغابايت.': 'Chaque fichier ne doit pas dépasser 10 Mo.',
    'نوع ملف غير مدعوم. الأنواع المقبولة: PDF وJPG وPNG.': 'Type de fichier non pris en charge. Formats acceptés : PDF, JPG et PNG.',
    'الكتابة': 'Greffière',
    'وحدة': 'Unité',
    'نوع المستخدم': 'Type d’utilisateur',
    'الدخول محمي بحساب مستخدم مسجل في قاعدة البيانات.': 'L’accès est protégé par un compte utilisateur enregistré dans la base de données.',
    'تسجيل الدخول إلى نظام الأرشيف': 'Se connecter au système d’archives',
    'حساب المسؤول': 'Compte administrateur',
    'إضافة الملك': 'Ajouter le bien',
    'لكل طرف تمت إضافته، أدخل الاسم والنسب والدور.': 'Pour chaque partie ajoutée, saisissez le prénom, le nom et le rôle.',
    'الأطراف المسموحة في عقد الزواج هي الزوج والزوجة والشاهد فقط.': 'Pour un acte de mariage, seules l’époux, l’épouse et le témoin sont autorisés.',
    'يجب إدخال بيانات الزوج والزوجة مرة واحدة لكل عقد زواج.': 'Les informations de l’époux et de l’épouse doivent être saisies une seule fois pour chaque acte de mariage.',
    'حجم الملف يتجاوز الحد المسموح.': 'La taille du fichier dépasse la limite autorisée.',
    'تعذر حفظ العقد.': 'Impossible d’enregistrer le contrat.',
    'عدد العقود المرتبطة': 'Nombre de contrats associés',
    'كل العقود': 'Tous les contrats',
    'معلومات المسؤول': 'Informations de l’administrateur',
    'معلومات الحساب': 'Informations du compte',
    'حفظ': 'Enregistrer',
    'الحالة': 'Statut',
    'إنشاء الحسابات وتفعيلها أو تعطيلها أو حذفها نهائياً إذا لم تكن مرتبطة بسجلات محفوظة': 'Créer, activer, désactiver ou supprimer définitivement les comptes non liés à des enregistrements',
    'أنت': 'Vous',
    'تم حفظ العقد والأطراف في قاعدة البيانات.': 'Le contrat et ses parties ont été enregistrés dans la base de données.',
    'اسم المستخدم أو كلمة المرور غير صحيحة.': 'Nom d’utilisateur ou mot de passe incorrect.',
    'يرجى إدخال اسم المستخدم وكلمة المرور.': 'Saisissez votre nom d’utilisateur et votre mot de passe.',
    'تعذر حفظ العقد:': 'Impossible d’enregistrer le contrat :',
    'رقم العقد مطلوب بالعربية والفرنسية ولا يتجاوز 80 حرفاً.': 'Le numéro du contrat est obligatoire en arabe et en français (80 caractères maximum).',
    'أدخل رقم الأرشيف والمرجع بالعربية والفرنسية.': 'Saisissez le numéro d’archive et la référence en arabe et en français.',
    'رقم الأرشيف أو المرجع يتجاوز الحد المسموح.': 'Le numéro d’archive ou la référence dépasse la limite autorisée.',
    'أدخل الملاحظات بالعربية والفرنسية.': 'Saisissez les notes en arabe et en français.',
    'الملاحظات طويلة جداً.': 'Les notes sont trop longues.',
    'أدخل الاسم والنسب والدور لكل طرف بالعربية والفرنسية.': 'Saisissez le prénom, le nom et le rôle de chaque partie en arabe et en français.',
    'بيانات أحد الأطراف تتجاوز الحد المسموح.': 'Les informations d’une partie dépassent la limite autorisée.',
    'أضف طرفاً واحداً على الأقل مع الاسم والنسب والدور.': 'Ajoutez au moins une partie avec son prénom, son nom et son rôle.',
    'قائمة الأملاك غير صالحة.': 'La liste des biens est invalide.',
    'الحد الأقصى هو 50 ملكاً في العقد الواحد.': 'Un contrat peut contenir au maximum 50 biens.',
    'بيانات أحد الأملاك غير صالحة.': 'Les informations d’un bien sont invalides.',
    'نوع أحد الأملاك غير صالح.': 'Le type d’un bien est invalide.',
    'خصائص أحد الأملاك غير صالحة.': 'Les caractéristiques d’un bien sont invalides.',
    'اختر نوعاً صحيحاً لكل ملك تمت إضافته.': 'Choisissez un type valide pour chaque bien ajouté.',
    'إحدى خصائص الأملاك غير صالحة.': 'Une caractéristique d’un bien est invalide.',
    'أدخل كل خاصية بالعربية والفرنسية.': 'Saisissez chaque caractéristique en arabe et en français.',
    'إحدى خصائص الأملاك طويلة جداً.': 'Une caractéristique d’un bien est trop longue.',
    'الخصائص الإضافية لأحد الأملاك غير صالحة.': 'Les caractéristiques supplémentaires d’un bien sont invalides.',
    'إحدى الخصائص الإضافية غير صالحة.': 'Une caractéristique supplémentaire est invalide.',
    'اسم أو قيمة إحدى الخصائص الإضافية غير صالح.': 'Le nom ou la valeur d’une caractéristique supplémentaire est invalide.',
    'أدخل اسماً وقيمة بالعربية والفرنسية لكل خاصية إضافية.': 'Saisissez le nom et la valeur en arabe et en français pour chaque caractéristique supplémentaire.',
    'النص المدخل طويل جداً.': 'Le texte saisi est trop long.',
    'تاريخ العقد مطلوب.': 'La date du contrat est obligatoire.',
    'قائمة الأطراف غير صالحة.': 'La liste des parties est invalide.',
    'يمكن إرفاق 10 ملفات كحد أقصى بالعقد الواحد.': 'Vous pouvez joindre au maximum 10 fichiers par contrat.',
    'تعذر استلام أحد الملفات. تحقق من حدود رفع الملفات في PHP.': 'Un fichier n’a pas pu être reçu. Vérifiez les limites de téléversement PHP.',
    'بيانات الملفات المرفوعة غير صالحة.': 'Les informations des fichiers téléversés sont invalides.',
    'تاريخ العقد غير صالح.': 'La date du contrat est invalide.',
    'تاريخ التسجيل غير صالح.': 'La date d’enregistrement est invalide.',
    'معرف العقد غير صالح.': 'Identifiant de contrat invalide.',
    'تأكيد حذف المستخدم': 'Confirmer la suppression de l’utilisateur',
    'معرف الشخص غير صالح.': 'Identifiant de personne invalide.',
    'الشخص غير موجود.': 'Personne introuvable.',
    'العقد غير موجود.': 'Contrat introuvable.',
    'معرف الوثيقة غير صالح.': 'Identifiant de document invalide.',
    'الوثيقة غير موجودة.': 'Document introuvable.',
    'ملف الوثيقة غير متاح.': 'Le fichier du document est indisponible.',
    'رقم العقد غير صالح.': 'Numéro de contrat invalide.',
    'بيانات الأطراف غير صالحة.': 'Les informations des parties sont invalides.',
    'نوع الملف غير مسموح.': 'Ce type de fichier n’est pas autorisé.',
    'تعذر حفظ الوثيقة.': 'Impossible d’enregistrer le document.',
    'الرجوع إلى القائمة': 'Retour à la liste',
    'عرض التفاصيل': 'Voir les détails',
    'تم حذف العقد.': 'Le contrat a été supprimé.',
    'العقد غير موجود أو سبق حذفه.': 'Le contrat est introuvable ou a déjà été supprimé.',
    'حذف العقد؟': 'Supprimer le contrat ?',
    'إعداد المسؤول': 'Configuration du compte administrateur',
    'إنشاء حساب المسؤول الأول': 'Créer le premier compte administrateur',
    'هذه الخطوة متاحة مرة واحدة فقط قبل إنشاء أي مستخدم.': 'Cette étape n’est disponible qu’une seule fois, avant la création du premier utilisateur.',
    'إنشاء حساب المسؤول': 'Créer le compte administrateur',
    'اسم المستخدم': 'Nom d’utilisateur',
    'كلمة المرور': 'Mot de passe',
    'معاينة': 'Aperçu',
    'غير محدد': 'Non spécifié',
    'غير صالح.': 'invalide.',
    'فتح القائمة': 'Ouvrir le menu'
  };

  const originalText = new WeakMap();
  const renderedText = new WeakMap();
  const originalAttributes = new WeakMap();
  let language = localStorage.getItem('archive-language') === 'fr' ? 'fr' : 'ar';
  const originalTitle = document.title;

  function translate(value, targetLanguage) {
    if (targetLanguage === 'ar') return value;
    const leading = value.match(/^\s*/)[0];
    const trailing = value.match(/\s*$/)[0];
    const phrase = value.slice(leading.length, value.length - trailing.length || undefined);
    let translated = translations[phrase];

    if (!translated && targetLanguage === 'fr') {
      const countLabel = phrase.match(/^(\d+)\s+(عقد|شخص)$/);
      if (countLabel) {
        const singular = countLabel[1] === '1';
        const unit = countLabel[2] === 'عقد'
          ? (singular ? 'contrat' : 'contrats')
          : (singular ? 'personne' : 'personnes');
        translated = `${countLabel[1]} ${unit}`;
      }
      const numberedLabel = phrase.match(/^(الشخص|ملك|الشاهد)\s+(\d+)$/);
      if (!translated && numberedLabel) {
        const label = translations[numberedLabel[1]];
        translated = `${label} ${numberedLabel[2]}`;
      } else if (!translated) {
        const propertyTitle = phrase.match(/^(سيارة|بقعة أرضية|منزل|محل تجاري|أخرى)\s+(\d+)$/);
        if (propertyTitle) translated = `${translations[propertyTitle[1]]} ${propertyTitle[2]}`;
      }
      if (!translated) {
        const resultRange = phrase.match(/^النتائج\s+(.+?)–(.+?)\s+من\s+(.+)$/);
        if (resultRange) translated = `Résultats ${resultRange[1]}–${resultRange[2]} sur ${resultRange[3]}`;
      }
      if (!translated) {
        const countedTitle = phrase.match(/^(.+?)\s+\((\d+)\)$/);
        if (countedTitle && translations[countedTitle[1]]) {
          translated = `${translations[countedTitle[1]]} (${countedTitle[2]})`;
        }
      }
      if (!translated) {
        const numberedTitle = phrase.match(/^(تعديل العقد)\s+(.+)$/);
        if (numberedTitle) translated = `${translations[numberedTitle[1]]} ${numberedTitle[2]}`;
      }
      if (!translated) {
        const dateError = phrase.match(/^(تاريخ العقد|تاريخ التسجيل) غير صالح\.$/);
        if (dateError) translated = `${translations[dateError[1]]} invalide.`;
      }
    }

    return translated ? `${leading}${translated}${trailing}` : value;
  }

  function updateTextNode(node) {
    if (node.parentElement?.closest('[data-bilingual-value]')) return;
    if (!originalText.has(node) || (renderedText.has(node) && renderedText.get(node) !== node.nodeValue)) {
      originalText.set(node, node.nodeValue);
    }
    const value = translate(originalText.get(node), language);
    if (node.nodeValue !== value) node.nodeValue = value;
    renderedText.set(node, value);
  }

  function updateAttribute(element, name) {
    if (!element.hasAttribute(name)) return;
    let values = originalAttributes.get(element);
    if (!values) {
      values = new Map();
      originalAttributes.set(element, values);
    }
    if (!values.has(name)) values.set(name, element.getAttribute(name));
    element.setAttribute(name, translate(values.get(name), language));
  }

  function updateTree(root) {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    while (walker.nextNode()) updateTextNode(walker.currentNode);
    if (root.nodeType === Node.ELEMENT_NODE) {
      const attributes = '[placeholder], [title], [aria-label], [alt], [onsubmit]';
      [root, ...root.querySelectorAll(attributes)].forEach((element) => {
        ['placeholder', 'title', 'aria-label', 'alt', 'onsubmit'].forEach((name) => updateAttribute(element, name));
      });
    }
  }

  function applyLanguage() {
    document.documentElement.lang = language;
    document.documentElement.dir = language === 'ar' ? 'rtl' : 'ltr';
    document.body.style.direction = language === 'ar' ? 'rtl' : 'ltr';
    document.title = originalTitle
      .split(' | ')
      .map((part) => translate(part, language))
      .join(' | ');
    document.querySelectorAll('[data-language-toggle]').forEach((button) => {
      button.textContent = language === 'ar' ? 'Français' : 'العربية';
    });
    document.querySelectorAll('[data-bilingual-value]').forEach((value) => {
      const arabic = value.querySelector('[data-value-ar]');
      const french = value.querySelector('[data-value-fr]');
      if (arabic) arabic.hidden = language !== 'ar';
      if (french) french.hidden = language !== 'fr';
    });
    updateTree(document.body);
    document.querySelectorAll('[data-language-toggle]').forEach((button) => {
      button.setAttribute('aria-label', language === 'ar' ? 'Passer au français' : 'التبديل إلى العربية');
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-language-toggle]').forEach((button) => {
      button.addEventListener('click', () => {
        language = language === 'ar' ? 'fr' : 'ar';
        localStorage.setItem('archive-language', language);
        applyLanguage();
      });
    });
    applyLanguage();
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === 'characterData') updateTextNode(mutation.target);
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType === Node.TEXT_NODE) updateTextNode(node);
          else if (node.nodeType === Node.ELEMENT_NODE) updateTree(node);
        });
      });
    });
    observer.observe(document.body, { subtree: true, childList: true, characterData: true });
  });
})();
