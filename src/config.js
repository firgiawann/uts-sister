import dotenv from 'dotenv';
dotenv.config();

export const config = {
  port: parseInt(process.env.PORT, 10) || 4000,
  nodeEnv: process.env.NODE_ENV || 'development',
  serviceName: 'Kelompok 4 - Assignment Service',
  version: '1.0.0',
  external: {
    gatewayUrl: process.env.KELOMPOK_1_GATEWAY_URL || 'http://localhost:8080',
    courseUrl: process.env.KELOMPOK_3_COURSE_URL || 'http://localhost:3000',
    submissionUrl: process.env.KELOMPOK_5_SUBMISSION_URL || 'http://localhost:5000',
    useMockFallback: process.env.USE_MOCK_FALLBACK !== 'false'
  }
};

export const updateExternalConfig = (newConfig) => {
  if (newConfig.gatewayUrl !== undefined) config.external.gatewayUrl = newConfig.gatewayUrl;
  if (newConfig.courseUrl !== undefined) config.external.courseUrl = newConfig.courseUrl;
  if (newConfig.submissionUrl !== undefined) config.external.submissionUrl = newConfig.submissionUrl;
  if (newConfig.useMockFallback !== undefined) config.external.useMockFallback = Boolean(newConfig.useMockFallback);
  return config.external;
};
