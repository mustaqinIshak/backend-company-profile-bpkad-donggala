const multer = require('multer');
const path = require('path');
const fs = require('fs');

const UPLOAD_PATH = process.env.UPLOAD_PATH || './uploads';
const MAX_FILE_SIZE = parseInt(process.env.MAX_FILE_SIZE) || 5 * 1024 * 1024; // 5MB

// Ensure upload directories exist
['jumbotron', 'berita', 'layanan', 'profile'].forEach((dir) => {
  const dirPath = path.join(UPLOAD_PATH, dir);
  if (!fs.existsSync(dirPath)) {
    fs.mkdirSync(dirPath, { recursive: true });
  }
});

const createStorage = (folder) =>
  multer.diskStorage({
    destination: (req, file, cb) => {
      cb(null, path.join(UPLOAD_PATH, folder));
    },
    filename: (req, file, cb) => {
      const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1e9);
      const ext = path.extname(file.originalname);
      cb(null, `${folder}-${uniqueSuffix}${ext}`);
    },
  });

const imageFilter = (req, file, cb) => {
  const allowedTypes = /jpeg|jpg|png|gif|webp/;
  const extname = allowedTypes.test(path.extname(file.originalname).toLowerCase());
  const mimetype = allowedTypes.test(file.mimetype);
  if (extname && mimetype) {
    cb(null, true);
  } else {
    cb(new Error('Hanya file gambar yang diizinkan (jpeg, jpg, png, gif, webp)'));
  }
};

const documentFilter = (req, file, cb) => {
  const allowedTypes = /pdf|doc|docx|xls|xlsx|ppt|pptx/;
  const allowedMimes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
  ];
  const extname = allowedTypes.test(path.extname(file.originalname).toLowerCase());
  const mimetype = allowedMimes.includes(file.mimetype);
  if (extname && mimetype) {
    cb(null, true);
  } else {
    cb(new Error('Hanya file dokumen yang diizinkan (pdf, doc, docx, xls, xlsx, ppt, pptx)'));
  }
};

const uploadJumbotron = multer({
  storage: createStorage('jumbotron'),
  limits: { fileSize: MAX_FILE_SIZE },
  fileFilter: imageFilter,
});

const uploadBerita = multer({
  storage: createStorage('berita'),
  limits: { fileSize: MAX_FILE_SIZE },
  fileFilter: imageFilter,
});

const uploadLayanan = multer({
  storage: createStorage('layanan'),
  limits: { fileSize: MAX_FILE_SIZE * 4 }, // 20MB for documents
  fileFilter: documentFilter,
});

const uploadProfile = multer({
  storage: createStorage('profile'),
  limits: { fileSize: MAX_FILE_SIZE },
  fileFilter: imageFilter,
});

module.exports = {
  uploadJumbotron,
  uploadBerita,
  uploadLayanan,
  uploadProfile,
};
