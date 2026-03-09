// storage.bicep
// Deployed at resource-group scope (default)

@description('Azure region for all resources')
param location string

@description('Unique suffix to ensure globally unique storage account names')
param nameSuffix string = uniqueString(resourceGroup().id)

@description('Storage account SKU')
param skuName string = 'Standard_LRS'

// --------------------------------------------------------------------------
// Storage account configurations
// --------------------------------------------------------------------------
var storageConfigs = [
  {
    key: 'default'
    namePart: 'default'
    softDeleteEnabled: false
    versioningEnabled: false
    allowPublicAccess: false
  }
  {
    key: 'public'
    namePart: 'public'
    softDeleteEnabled: false
    versioningEnabled: false
    allowPublicAccess: true
  }
  {
    key: 'softdeletes'
    namePart: 'softdel'
    softDeleteEnabled: true
    versioningEnabled: false
    allowPublicAccess: false
  }
  {
    key: 'versions'
    namePart: 'ver'
    softDeleteEnabled: false
    versioningEnabled: true
    allowPublicAccess: false
  }
  {
    key: 'softdeletesversions'
    namePart: 'sdver'
    softDeleteEnabled: true
    versioningEnabled: true
    allowPublicAccess: false
  }
]

// --------------------------------------------------------------------------
// Storage Accounts
// --------------------------------------------------------------------------
resource storageAccounts 'Microsoft.Storage/storageAccounts@2023-01-01' = [
  for config in storageConfigs: {
    name: toLower('st${config.namePart}${nameSuffix}')
    location: location
    kind: 'StorageV2'
    sku: {
      name: skuName
    }
    properties: {
      accessTier: 'Hot'
      supportsHttpsTrafficOnly: true
      minimumTlsVersion: 'TLS1_2'
      allowBlobPublicAccess: config.allowPublicAccess
      allowSharedKeyAccess: true
      encryption: {
        services: {
          blob: {
            keyType: 'Account'
            enabled: true
          }
          file: {
            keyType: 'Account'
            enabled: true
          }
        }
        keySource: 'Microsoft.Storage'
      }
      networkAcls: {
        bypass: 'AzureServices'
        defaultAction: 'Allow'
      }
    }
  }
]

// --------------------------------------------------------------------------
// Blob Services — soft delete & versioning per account
// --------------------------------------------------------------------------
resource blobServices 'Microsoft.Storage/storageAccounts/blobServices@2023-01-01' = [
  for (config, i) in storageConfigs: {
    parent: storageAccounts[i]
    name: 'default'
    properties: {
      deleteRetentionPolicy: {
        enabled: config.softDeleteEnabled
        days: config.softDeleteEnabled ? 30 : 1
      }
      containerDeleteRetentionPolicy: {
        enabled: config.softDeleteEnabled
        days: config.softDeleteEnabled ? 30 : 1
      }
      isVersioningEnabled: config.versioningEnabled
    }
  }
]

// --------------------------------------------------------------------------
// Outputs — connection strings
// --------------------------------------------------------------------------
var suffix = environment().suffixes.storage

output AZURE_STORAGE_CONNECTION_STRING string = 'DefaultEndpointsProtocol=https;AccountName=${storageAccounts[0].name};AccountKey=${storageAccounts[0].listKeys().keys[0].value};EndpointSuffix=${suffix}'

output AZURE_STORAGE_CONNECTION_STRING_PUBLIC string = 'DefaultEndpointsProtocol=https;AccountName=${storageAccounts[1].name};AccountKey=${storageAccounts[1].listKeys().keys[0].value};EndpointSuffix=${suffix}'

output AZURE_STORAGE_CONNECTION_STRING_SOFT_DELETES string = 'DefaultEndpointsProtocol=https;AccountName=${storageAccounts[2].name};AccountKey=${storageAccounts[2].listKeys().keys[0].value};EndpointSuffix=${suffix}'

output AZURE_STORAGE_CONNECTION_STRING_VERSIONS string = 'DefaultEndpointsProtocol=https;AccountName=${storageAccounts[3].name};AccountKey=${storageAccounts[3].listKeys().keys[0].value};EndpointSuffix=${suffix}'

output AZURE_STORAGE_CONNECTION_STRING_SOFT_DELETES_VERSIONS string = 'DefaultEndpointsProtocol=https;AccountName=${storageAccounts[4].name};AccountKey=${storageAccounts[4].listKeys().keys[0].value};EndpointSuffix=${suffix}'