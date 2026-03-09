// main.bicep
targetScope = 'subscription'

@description('Name of the resource group to create')
param resourceGroupName string = 'rg-storage-test'

@description('Azure region for all resources')
param location string = 'eastus'

// --------------------------------------------------------------------------
// Create the Resource Group
// --------------------------------------------------------------------------
resource rg 'Microsoft.Resources/resourceGroups@2024-03-01' = {
  name: resourceGroupName
  location: location
}

// --------------------------------------------------------------------------
// Deploy storage accounts into the new resource group via module
// --------------------------------------------------------------------------
module storageModule 'storage.bicep' = {
  name: 'storageDeployment'
  scope: rg
  params: {
    location: location
  }
}

// --------------------------------------------------------------------------
// Surface connection strings from the module
// --------------------------------------------------------------------------
output AZURE_STORAGE_CONNECTION_STRING string = storageModule.outputs.AZURE_STORAGE_CONNECTION_STRING
output AZURE_STORAGE_CONNECTION_STRING_PUBLIC string = storageModule.outputs.AZURE_STORAGE_CONNECTION_STRING_PUBLIC
output AZURE_STORAGE_CONNECTION_STRING_SOFT_DELETES string = storageModule.outputs.AZURE_STORAGE_CONNECTION_STRING_SOFT_DELETES
output AZURE_STORAGE_CONNECTION_STRING_VERSIONS string = storageModule.outputs.AZURE_STORAGE_CONNECTION_STRING_VERSIONS
output AZURE_STORAGE_CONNECTION_STRING_SOFT_DELETES_VERSIONS string = storageModule.outputs.AZURE_STORAGE_CONNECTION_STRING_SOFT_DELETES_VERSIONS