import React from 'react';
import './App.css';
import { HydraAdmin } from '@api-platform/admin';

export default () => <HydraAdmin entrypoint="http://127.0.0.1:8000/api"/>;
