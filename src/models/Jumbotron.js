const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Jumbotron = sequelize.define('Jumbotron', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true,
  },
  judul: {
    type: DataTypes.STRING(255),
    allowNull: true,
  },
  deskripsi: {
    type: DataTypes.TEXT,
    allowNull: true,
  },
  gambar: {
    type: DataTypes.STRING(255),
    allowNull: false,
  },
  urutan: {
    type: DataTypes.INTEGER,
    defaultValue: 0,
  },
  aktif: {
    type: DataTypes.BOOLEAN,
    defaultValue: true,
  },
}, {
  tableName: 'jumbotrons',
  timestamps: true,
});

module.exports = Jumbotron;
